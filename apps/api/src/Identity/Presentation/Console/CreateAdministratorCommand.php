<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Console;

use App\Identity\Application\AccountLinkSender;
use App\Identity\Application\Output\UserView;
use App\Identity\Application\PasswordPolicy;
use App\Identity\Application\UserGuard;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Shared\Application\AuditTrail;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * First administrator of an installation. Without --password the user gets the usual activation SMS and the link
 * is printed too; with --password the account is active at once (CI smoke tests, scripted installs).
 */
#[AsCommand(name: 'app:user:create-admin', description: 'Create an administrator (first user of an installation)')]
final readonly class CreateAdministratorCommand
{
    public function __construct(
        private UserRepository $users,
        private UserGuard $guard,
        private PasswordPolicy $policy,
        private UserPasswordHasherInterface $passwordHasher,
        private AccountLinkSender $links,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Full name, as it will appear in the office')]
        string $name,
        #[Argument(description: 'Mobile phone: the login identifier (+34600123456 or 600123456)')]
        string $phone,
        #[Option(description: 'Set this password and activate now, instead of sending the activation SMS')]
        ?string $password = null,
        #[Option(description: 'Optional e-mail')]
        ?string $email = null,
    ): int {
        try {
            $mobile = PhoneNumber::fromString($phone);
            $this->guard->assertUnique($mobile, $email, null);
            $user = new User($name, $mobile, [Role::Admin], $email);

            $link = $this->entityManager->wrapInTransaction(function () use ($user, $password): ?string {
                $this->users->add($user);
                $link = null;
                if (null === $password) {
                    $link = $this->links->send($user, AccountLinkType::Activation);
                } else {
                    $this->policy->check($password, $user);
                    $user->activate($this->passwordHasher->hashPassword($user, $password));
                }
                $this->audit->record('user.created', 'user', $user->getId(), after: UserView::snapshot($user));

                return $link;
            });
        } catch (ValidationFailed $exception) {
            $io->error(array_map(static fn (array $violation): string => $violation['field'].': '.$violation['code'], $exception->violations));

            return Command::FAILURE;
        } catch (DomainException $exception) {
            $io->error($exception->errorCode);

            return Command::FAILURE;
        }

        $io->success(\sprintf('Administrator %s created (%s).', $user->getFullName(), $user->getMobilePhone()));
        if (null !== $link) {
            $io->text(['Activation link (also sent by SMS, valid 72 hours):', $link]);
        }

        return Command::SUCCESS;
    }
}
