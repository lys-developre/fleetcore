<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;

/**
 * Rules of formularios-05 that hold across user management (reglas 3.3): unique identifiers, nobody acts
 * on their own account through the admin screens, and there is always an active administrator.
 * The database repeats the last rule with a trigger.
 */
final readonly class UserGuard
{
    public function __construct(private UserRepository $users)
    {
    }

    public function assertUnique(?PhoneNumber $phone, ?string $email, ?string $operatorNumber, ?User $except = null): void
    {
        $violations = [];
        $taken = static fn (?User $other): bool => null !== $other && $other !== $except;

        if (null !== $phone && $taken($this->users->findByPhone($phone))) {
            $violations[] = ['field' => 'phone', 'code' => 'phone_taken'];
        }
        if (null !== $email && '' !== trim($email) && $taken($this->users->findByEmail($email))) {
            $violations[] = ['field' => 'email', 'code' => 'email_taken'];
        }
        if (null !== $operatorNumber && '' !== trim($operatorNumber) && $taken($this->users->findByOperatorNumber($operatorNumber))) {
            $violations[] = ['field' => 'operatorNumber', 'code' => 'operator_number_taken'];
        }

        if ([] !== $violations) {
            throw ValidationFailed::withViolations($violations);
        }
    }

    public function assertNotSelf(User $target, User $actor): void
    {
        if ($target === $actor) {
            throw BusinessRuleViolation::because('cannot_target_self', 'Users cannot block, deactivate or delete their own account.');
        }
    }

    /** Call before a change that could take $target out of the active administrators. */
    public function assertAnotherActiveAdminRemains(User $target): void
    {
        if ($target->isActiveAdmin() && $this->users->countActiveAdmins() <= 1) {
            throw BusinessRuleViolation::because('last_active_admin', 'There must always be at least one active administrator.');
        }
    }

    /**
     * @param list<Role> $newRoles
     */
    public function assertRoleChangeAllowed(User $target, array $newRoles, User $actor): void
    {
        if (\in_array(Role::Admin, $newRoles, true) || !$target->hasRole(Role::Admin)) {
            return;
        }
        if ($target === $actor) {
            throw BusinessRuleViolation::because('cannot_remove_own_admin', 'Administrators cannot remove their own administrator role.');
        }
        $this->assertAnotherActiveAdminRemains($target);
    }
}
