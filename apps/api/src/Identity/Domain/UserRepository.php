<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use App\Shared\Domain\PhoneNumber;

interface UserRepository
{
    public function add(User $user): void;

    public function remove(User $user): void;

    public function findByPhone(PhoneNumber $phone): ?User;

    public function findByEmail(string $email): ?User;

    public function findByOperatorNumber(string $operatorNumber): ?User;

    /** @return list<User> ordered by full name */
    public function all(): array;

    /** Active users holding ROLE_ADMIN: the invariant "at least one" is checked against this. */
    public function countActiveAdmins(): int;
}
