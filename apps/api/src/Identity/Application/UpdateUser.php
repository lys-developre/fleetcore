<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\UpdateUserInput;
use App\Identity\Application\Output\UserView;
use App\Identity\Domain\User;
use App\Shared\Application\AuditTrail;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UpdateUser
{
    public function __construct(
        private UserGuard $guard,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(User $target, UpdateUserInput $input, User $actor): UserView
    {
        $roles = $input->roleList();
        $this->guard->assertUnique(null, $input->email, $input->operatorNumber, except: $target);
        $this->guard->assertRoleChangeAllowed($target, $roles, $actor);

        $before = UserView::snapshot($target);
        $target->updateProfile($input->fullName, $input->email, $input->operatorNumber);
        $target->replaceRoles($roles);
        $this->audit->record('user.updated', 'user', $target->getId(), $before, UserView::snapshot($target));
        $this->entityManager->flush();

        return UserView::from($target);
    }
}
