<?php

declare(strict_types=1);

namespace App\Factory;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use App\Shared\Domain\PhoneNumber;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Users for fixtures and tests. `password` null leaves the user pending activation; `status` then moves an
 * active user to blocked or deactivated through the entity's own transitions.
 *
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public const string DEFAULT_PASSWORD = 'cazo-martillo-2026';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    public function admin(): self
    {
        return $this->with(['roles' => [Role::Admin]]);
    }

    public function office(): self
    {
        return $this->with(['roles' => [Role::Office]]);
    }

    public function operator(): self
    {
        return $this->with(['roles' => [Role::Operator]]);
    }

    public function viewer(): self
    {
        return $this->with(['roles' => [Role::Viewer]]);
    }

    public function pending(): self
    {
        return $this->with(['password' => null]);
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'fullName' => self::faker()->firstName().' '.self::faker()->lastName(),
            'phone' => '+346'.self::faker()->unique()->numerify('########'),
            'roles' => [Role::Operator],
            'email' => null,
            'operatorNumber' => null,
            'password' => self::DEFAULT_PASSWORD,
            'status' => UserStatus::Active,
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::use(
            function (string $fullName, string $phone, array $roles, ?string $email, ?string $operatorNumber, ?string $password, UserStatus $status): User {
                /** @var list<Role> $roles */
                $user = new User($fullName, PhoneNumber::fromString($phone), $roles, $email, $operatorNumber);
                if (null === $password) {
                    return $user;
                }

                $user->activate($this->passwordHasher->hashPassword($user, $password));
                match ($status) {
                    UserStatus::Blocked => $user->block('Bloqueado en pruebas'),
                    UserStatus::Deactivated => $user->deactivate('Baja en pruebas'),
                    default => null,
                };

                return $user;
            },
        ));
    }
}
