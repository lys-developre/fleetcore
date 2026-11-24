<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use App\Shared\Application\AuditTrail;
use App\Shared\Domain\Exception\InvalidTransition;
use Doctrine\ORM\EntityManagerInterface;

/** "Reenviar" when the 72-hour activation link expired or got lost. The previous link stops working. */
final readonly class ResendActivation
{
    public function __construct(
        private AccountLinkSender $links,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(User $target): void
    {
        if (UserStatus::PendingActivation !== $target->getStatus()) {
            throw InvalidTransition::because('user_not_pending_activation', 'The user already set a password.');
        }

        $this->entityManager->wrapInTransaction(function () use ($target): void {
            $this->links->send($target, AccountLinkType::Activation);
            $this->audit->record('user.activation_resent', 'user', $target->getId());
        });
    }
}
