<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\AccessToken;
use App\Shared\Application\AuditTrail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/** Closes the session of the device making the request. */
final readonly class LogOut
{
    public function __construct(
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(AccessToken $current): void
    {
        $current->revoke($this->clock->now());
        $this->audit->record('auth.logout', 'user', $current->getUser()->getId(), actor: $current->getUser());
        $this->entityManager->flush();
    }
}
