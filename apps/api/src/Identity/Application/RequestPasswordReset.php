<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\RequestPasswordResetInput;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\UserRepository;
use App\Identity\Domain\UserStatus;
use App\Shared\Application\AuditTrail;
use App\Shared\Application\ClientContext;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * "He olvidado mi contraseña" (sección 6.5). Always ends silently: the caller answers the same whether or not
 * the phone exists, is active, or hit the limit of 3 requests per hour.
 */
final readonly class RequestPasswordReset
{
    public function __construct(
        private UserRepository $users,
        private AccountLinkSender $links,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private RateLimiterFactoryInterface $passwordResetIpLimiter,
        private RateLimiterFactoryInterface $passwordResetPhoneLimiter,
    ) {
    }

    public function __invoke(RequestPasswordResetInput $input, ClientContext $client): void
    {
        if (!$this->passwordResetIpLimiter->create($client->ip ?? 'unknown')->consume()->isAccepted()) {
            return;
        }

        $phone = PhoneNumber::normalize($input->phone);
        if (null === $phone || !$this->passwordResetPhoneLimiter->create($phone)->consume()->isAccepted()) {
            return;
        }

        $user = $this->users->findByPhone(PhoneNumber::fromString($phone));
        if (null === $user || UserStatus::Active !== $user->getStatus()) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $this->links->send($user, AccountLinkType::PasswordReset);
            $this->audit->record('auth.password_reset_requested', 'user', $user->getId());
        });
    }
}
