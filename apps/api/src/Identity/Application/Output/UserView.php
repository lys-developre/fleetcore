<?php

declare(strict_types=1);

namespace App\Identity\Application\Output;

use App\Identity\Domain\User;

/** A user as the API shows it. Never the entity: no hash, no lockout internals. */
final readonly class UserView
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $fullName,
        public string $phone,
        public ?string $email,
        public ?string $operatorNumber,
        public array $roles,
        public string $status,
        public ?string $statusReason,
        public ?\DateTimeImmutable $lockedUntil,
        public ?\DateTimeImmutable $lastLoginAt,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function from(User $user, ?\DateTimeImmutable $now = null): self
    {
        $lockedUntil = $user->getLockedUntil();

        return new self(
            id: $user->getId()->toRfc4122(),
            fullName: $user->getFullName(),
            phone: $user->getMobilePhone()->value,
            email: $user->getEmail(),
            operatorNumber: $user->getOperatorNumber(),
            roles: $user->getRoles(),
            status: $user->getStatus()->value,
            statusReason: $user->getStatusReason(),
            lockedUntil: null !== $now && null !== $lockedUntil && $lockedUntil <= $now ? null : $lockedUntil,
            lastLoginAt: $user->getLastLoginAt(),
            createdAt: $user->getCreatedAt(),
        );
    }

    /**
     * What the audit log keeps of a user before and after a change.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(User $user): array
    {
        return [
            'fullName' => $user->getFullName(),
            'phone' => $user->getMobilePhone()->value,
            'email' => $user->getEmail(),
            'operatorNumber' => $user->getOperatorNumber(),
            'roles' => $user->getRoles(),
            'status' => $user->getStatus()->value,
        ];
    }
}
