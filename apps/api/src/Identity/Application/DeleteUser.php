<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Output\UserView;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Shared\Application\AuditTrail;
use App\Shared\Domain\AuditLog;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Physical deletion, only for users without activity (regla 3.3.1): anyone who ever signed in has audit
 * entries as actor and must be deactivated instead. Reports will add their own check in H4.
 */
final readonly class DeleteUser
{
    public function __construct(
        private UserRepository $users,
        private UserGuard $guard,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(User $target, User $actor): void
    {
        $this->guard->assertNotSelf($target, $actor);
        $this->guard->assertAnotherActiveAdminRemains($target);

        $activity = $this->entityManager->getRepository(AuditLog::class)->count(['actorId' => $target->getId()]);
        if ($activity > 0) {
            throw BusinessRuleViolation::because('user_has_activity', 'Users with recorded activity are deactivated, not deleted.');
        }

        $this->audit->record('user.deleted', 'user', $target->getId(), before: UserView::snapshot($target));
        $this->users->remove($target);
        $this->entityManager->flush();
    }
}
