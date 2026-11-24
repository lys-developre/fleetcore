<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use App\Shared\Domain\Actor;
use App\Shared\Domain\Exception\InvalidTransition;
use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;
use App\Shared\Domain\RowAudit;
use App\Shared\Domain\RowAudited;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Anyone with access: administration, office, operators, viewers (formularios-05, sección 3).
 * The mobile phone is the login identifier. The site foreman is not a user: see ClientContact.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_app_user_mobile_phone', columns: ['mobile_phone'])]
#[ORM\UniqueConstraint(name: 'uniq_app_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_app_user_operator_number', columns: ['operator_number'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, Actor, RowAudited
{
    use RowAudit;

    public const int MAX_FAILED_LOGINS = 5;
    private const int FIRST_LOCKOUT_MINUTES = 15;
    private const int MAX_LOCKOUT_MINUTES = 240;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(length: 150)]
    private string $fullName;

    #[ORM\Column(length: 20)]
    private string $mobilePhone;

    #[ORM\Column(length: 254, nullable: true)]
    private ?string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $operatorNumber;

    /** @var Collection<int, UserRole> */
    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    private Collection $roles;

    #[ORM\Column(nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(length: 20, enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::PendingActivation;

    /** Reason given when blocking or deactivating. Cleared when the user is active again. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusReason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    /** Lockouts within 24 h of each other: each one doubles the next (sección 6.4). */
    #[ORM\Column(options: ['default' => 0])]
    private int $recentLockouts = 0;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLockoutAt = null;

    /**
     * @param list<Role> $roles
     */
    public function __construct(string $fullName, PhoneNumber $mobilePhone, array $roles, ?string $email = null, ?string $operatorNumber = null)
    {
        $this->id = Uuid::v7();
        $this->roles = new ArrayCollection();
        $this->mobilePhone = $mobilePhone->value;
        $this->updateProfile($fullName, $email, $operatorNumber);
        $this->replaceRoles($roles);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getMobilePhone(): PhoneNumber
    {
        return PhoneNumber::fromString($this->mobilePhone);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getOperatorNumber(): ?string
    {
        return $this->operatorNumber;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getStatusReason(): ?string
    {
        return $this->statusReason;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function hasPassword(): bool
    {
        return null !== $this->passwordHash;
    }

    // ---- profile and roles ----

    public function updateProfile(string $fullName, ?string $email, ?string $operatorNumber): void
    {
        $fullName = (string) preg_replace('/\s+/u', ' ', trim($fullName));
        if ('' === $fullName) {
            throw ValidationFailed::forField('fullName', 'required');
        }
        $email = null === $email ? null : mb_strtolower(trim($email));
        $operatorNumber = null === $operatorNumber ? null : trim($operatorNumber);

        $this->fullName = $fullName;
        $this->email = '' === $email ? null : $email;
        $this->operatorNumber = '' === $operatorNumber ? null : $operatorNumber;
    }

    /**
     * @param list<Role> $roles
     */
    public function replaceRoles(array $roles): void
    {
        if ([] === $roles) {
            throw ValidationFailed::forField('roles', 'roles_required');
        }
        foreach ($this->roles as $userRole) {
            if (!\in_array($userRole->getRole(), $roles, true)) {
                $this->roles->removeElement($userRole);
            }
        }
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                $this->roles->add(new UserRole($this, $role));
            }
        }
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles->exists(static fn (int $key, UserRole $userRole): bool => $userRole->getRole() === $role);
    }

    /** @return list<Role> */
    public function getRoleList(): array
    {
        $roles = array_map(static fn (UserRole $userRole): Role => $userRole->getRole(), $this->roles->toArray());
        usort($roles, static fn (Role $a, Role $b): int => array_search($a, Role::cases(), true) <=> array_search($b, Role::cases(), true));

        return $roles;
    }

    /** An operator and nothing else: the only users office staff may manage (matriz 5.4). */
    public function isOnlyOperator(): bool
    {
        return [Role::Operator] === $this->getRoleList();
    }

    public function isActiveAdmin(): bool
    {
        return UserStatus::Active === $this->status && $this->hasRole(Role::Admin);
    }

    // ---- status transitions (sección 3.2) ----

    public function activate(string $passwordHash): void
    {
        if (UserStatus::PendingActivation !== $this->status) {
            throw InvalidTransition::because('user_not_pending_activation', 'Only users pending activation can be activated.');
        }
        $this->status = UserStatus::Active;
        $this->changePassword($passwordHash);
    }

    public function block(?string $reason): void
    {
        if (UserStatus::Active !== $this->status) {
            throw InvalidTransition::because('user_not_active', 'Only active users can be blocked.');
        }
        $this->status = UserStatus::Blocked;
        $this->statusReason = self::reason($reason);
    }

    public function unblock(): void
    {
        if (UserStatus::Blocked !== $this->status) {
            throw InvalidTransition::because('user_not_blocked', 'Only blocked users can be unblocked.');
        }
        $this->status = UserStatus::Active;
        $this->statusReason = null;
    }

    public function deactivate(string $reason): void
    {
        if (!\in_array($this->status, [UserStatus::Active, UserStatus::Blocked], true)) {
            throw InvalidTransition::because('user_not_deactivatable', 'Only active or blocked users can be deactivated.');
        }
        if (null === self::reason($reason)) {
            throw ValidationFailed::forField('reason', 'required');
        }
        $this->status = UserStatus::Deactivated;
        $this->statusReason = self::reason($reason);
    }

    public function readmit(): void
    {
        if (UserStatus::Deactivated !== $this->status) {
            throw InvalidTransition::because('user_not_deactivated', 'Only deactivated users can be readmitted.');
        }
        $this->status = null === $this->passwordHash ? UserStatus::PendingActivation : UserStatus::Active;
        $this->statusReason = null;
    }

    // ---- password and login attempts (sección 6.4) ----

    public function changePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
    }

    public function isLockedAt(\DateTimeImmutable $now): bool
    {
        return null !== $this->lockedUntil && $this->lockedUntil > $now;
    }

    /**
     * Five failures in a row lock the account for 15 minutes. Each further lockout within 24 hours
     * doubles the time, up to 4 hours.
     *
     * @return bool whether this failure locked the account
     */
    public function registerFailedLogin(\DateTimeImmutable $now): bool
    {
        ++$this->failedLoginAttempts;
        if ($this->failedLoginAttempts < self::MAX_FAILED_LOGINS) {
            return false;
        }

        if (null === $this->lastLockoutAt || $this->lastLockoutAt < $now->modify('-24 hours')) {
            $this->recentLockouts = 0;
        }
        $minutes = min(self::FIRST_LOCKOUT_MINUTES * (2 ** $this->recentLockouts), self::MAX_LOCKOUT_MINUTES);

        $this->lockedUntil = $now->modify(\sprintf('+%d minutes', $minutes));
        $this->lastLockoutAt = $now;
        ++$this->recentLockouts;
        $this->failedLoginAttempts = 0;

        return true;
    }

    public function registerSuccessfulLogin(\DateTimeImmutable $now): void
    {
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
        $this->lastLoginAt = $now;
    }

    /** Office or administration lifting a temporary lockout. */
    public function unlock(): void
    {
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
    }

    // ---- Symfony Security ----

    public function getUserIdentifier(): string
    {
        return $this->id->toRfc4122();
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_map(static fn (Role $role): string => $role->value, $this->getRoleList());
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    private static function reason(?string $reason): ?string
    {
        $reason = null === $reason ? null : trim($reason);

        return null === $reason || '' === $reason ? null : mb_substr($reason, 0, 255);
    }
}
