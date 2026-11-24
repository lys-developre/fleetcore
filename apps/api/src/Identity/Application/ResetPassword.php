<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\SetPasswordInput;
use App\Identity\Application\Output\IssuedSession;
use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\AccountLinkType;
use App\Shared\Application\AuditTrail;
use App\Shared\Application\ClientContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * New password from a recovery link. Every other session is closed (whoever knew the old password is out),
 * the lockout counter resets, and this device is signed in.
 */
final readonly class ResetPassword
{
    public function __construct(
        private AccountLinkResolver $resolver,
        private PasswordPolicy $policy,
        private UserPasswordHasherInterface $passwordHasher,
        private AccessTokenRepository $tokens,
        private SessionIssuer $sessions,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $plainToken, SetPasswordInput $input, ClientContext $client): IssuedSession
    {
        $link = $this->resolver->resolve($plainToken, AccountLinkType::PasswordReset, $client);
        $user = $link->getUser();
        $this->policy->check($input->password, $user);

        $now = $this->clock->now();
        $user->changePassword($this->passwordHasher->hashPassword($user, $input->password));
        $link->markUsed($now);
        $this->tokens->revokeAllFor($user, $now);
        $session = $this->sessions->issue($user, $input->sessionProfile(), $client);
        $this->audit->record('auth.password_reset', 'user', $user->getId(), actor: $user);
        $this->entityManager->flush();

        return $session;
    }
}
