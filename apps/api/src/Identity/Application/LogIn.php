<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\LoginInput;
use App\Identity\Application\Output\IssuedSession;
use App\Identity\Domain\Exception\AccountDisabled;
use App\Identity\Domain\Exception\InvalidCredentials;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Identity\Domain\UserStatus;
use App\Shared\Application\AuditTrail;
use App\Shared\Application\ClientContext;
use App\Shared\Domain\AuditResult;
use App\Shared\Domain\Exception\TooManyAttempts;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Phone and password in, device token out (formularios-05, secciones 6.1 y 6.4).
 *
 * Unknown phones and wrong passwords get the same answer and take the same time. Known accounts lock
 * progressively; unknown phones are throttled by a limiter with the same threshold, so a 429 says nothing either.
 */
final readonly class LogIn
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordHasherFactoryInterface $hasherFactory,
        private SessionIssuer $sessions,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private RateLimiterFactoryInterface $loginIpLimiter,
        private RateLimiterFactoryInterface $loginUnknownPhoneLimiter,
    ) {
    }

    public function __invoke(LoginInput $input, ClientContext $client): IssuedSession
    {
        $now = $this->clock->now();

        $byIp = $this->loginIpLimiter->create($client->ip ?? 'unknown')->consume();
        if (!$byIp->isAccepted()) {
            throw new TooManyAttempts($byIp->getRetryAfter());
        }

        $phone = PhoneNumber::normalize($input->phone);
        $user = null === $phone ? null : $this->users->findByPhone(PhoneNumber::fromString($phone));

        if (null === $user || !$user->hasPassword()) {
            $byPhone = $this->loginUnknownPhoneLimiter->create($phone ?? $input->phone)->consume();
            // Same cost as a real verification, so response time does not reveal registered phones
            $this->hasherFactory->getPasswordHasher(User::class)->hash($input->password);
            if (!$byPhone->isAccepted()) {
                throw new TooManyAttempts($byPhone->getRetryAfter());
            }
            throw new InvalidCredentials();
        }

        $lockedUntil = $user->getLockedUntil();
        if (null !== $lockedUntil && $user->isLockedAt($now)) {
            throw new TooManyAttempts($lockedUntil);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $input->password)) {
            $locked = $user->registerFailedLogin($now);
            $this->audit->record('auth.login_failed', 'user', $user->getId(), after: ['locked' => $locked], result: AuditResult::Denied, actor: $user);
            $this->entityManager->flush();

            $lockedUntil = $user->getLockedUntil();
            if ($locked && null !== $lockedUntil) {
                throw new TooManyAttempts($lockedUntil);
            }
            throw new InvalidCredentials();
        }

        if (UserStatus::Active !== $user->getStatus()) {
            $this->audit->record('auth.login_refused', 'user', $user->getId(), after: ['status' => $user->getStatus()->value], result: AuditResult::Denied, actor: $user);
            $this->entityManager->flush();
            throw new AccountDisabled($user->getStatus());
        }

        if ($this->passwordHasher->needsRehash($user)) {
            $user->changePassword($this->passwordHasher->hashPassword($user, $input->password));
        }

        $session = $this->sessions->issue($user, $input->sessionProfile(), $client);
        $this->audit->record('auth.login', 'user', $user->getId(), after: ['profile' => $session->profile], actor: $user);
        $this->entityManager->flush();

        return $session;
    }
}
