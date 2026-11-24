<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\UpdateOwnProfileInput;
use App\Identity\Application\Output\UserView;
use App\Identity\Domain\User;
use App\Shared\Application\AuditTrail;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UpdateOwnProfile
{
    public function __construct(
        private UserGuard $guard,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(User $user, UpdateOwnProfileInput $input): UserView
    {
        $this->guard->assertUnique(null, $input->email, null, except: $user);

        $before = UserView::snapshot($user);
        $user->updateProfile($user->getFullName(), $input->email, $user->getOperatorNumber());
        $this->audit->record('user.updated', 'user', $user->getId(), $before, UserView::snapshot($user));
        $this->entityManager->flush();

        return UserView::from($user);
    }
}
