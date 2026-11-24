<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\CreateUserInput;
use App\Identity\Application\Output\UserView;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Shared\Application\AuditTrail;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;

/** Office registers a user; the activation SMS goes out in the same transaction (sección 6.2). */
final readonly class CreateUser
{
    public function __construct(
        private UserRepository $users,
        private UserGuard $guard,
        private AccountLinkSender $links,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(CreateUserInput $input): UserView
    {
        $phone = PhoneNumber::fromString($input->phone);
        $this->guard->assertUnique($phone, $input->email, $input->operatorNumber);

        $user = new User($input->fullName, $phone, $input->roleList(), $input->email, $input->operatorNumber);

        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $this->users->add($user);
            $this->links->send($user, AccountLinkType::Activation);
            $this->audit->record('user.created', 'user', $user->getId(), after: UserView::snapshot($user));
        });

        return UserView::from($user);
    }
}
