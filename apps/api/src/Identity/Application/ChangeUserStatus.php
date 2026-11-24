<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Output\UserView;
use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\User;
use App\Shared\Application\AuditTrail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Status changes made by office or administration (sección 3.2). Blocking and deactivating close every
 * session: the device loses access on its next request. Who may do what is UserVoter's decision.
 */
final readonly class ChangeUserStatus
{
    public function __construct(
        private UserGuard $guard,
        private AccessTokenRepository $tokens,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function block(User $target, ?string $reason, User $actor): UserView
    {
        $this->guard->assertNotSelf($target, $actor);
        $this->guard->assertAnotherActiveAdminRemains($target);
        $target->block($reason);
        $this->tokens->revokeAllFor($target, $this->clock->now());

        return $this->done('user.blocked', $target, ['reason' => $reason]);
    }

    public function unblock(User $target): UserView
    {
        $target->unblock();

        return $this->done('user.unblocked', $target);
    }

    /** Lifts the temporary lock after failed logins (sección 6.4). */
    public function unlock(User $target): UserView
    {
        $target->unlock();

        return $this->done('user.unlocked', $target);
    }

    public function deactivate(User $target, string $reason, User $actor): UserView
    {
        $this->guard->assertNotSelf($target, $actor);
        $this->guard->assertAnotherActiveAdminRemains($target);
        $target->deactivate($reason);
        $this->tokens->revokeAllFor($target, $this->clock->now());

        return $this->done('user.deactivated', $target, ['reason' => $reason]);
    }

    public function readmit(User $target): UserView
    {
        $target->readmit();

        return $this->done('user.readmitted', $target);
    }

    /**
     * @param array<string, mixed> $details
     */
    private function done(string $action, User $target, array $details = []): UserView
    {
        $this->audit->record($action, 'user', $target->getId(), after: ['status' => $target->getStatus()->value, ...$details]);
        $this->entityManager->flush();

        return UserView::from($target, $this->clock->now());
    }
}
