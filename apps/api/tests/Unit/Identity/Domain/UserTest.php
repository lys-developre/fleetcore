<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use App\Shared\Domain\Exception\InvalidTransition;
use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;
use PHPUnit\Framework\TestCase;

/** formularios-05, secciones 3 y 6.4. */
final class UserTest extends TestCase
{
    public function testStartsPendingActivationWithoutPassword(): void
    {
        $user = self::user();

        self::assertSame(UserStatus::PendingActivation, $user->getStatus());
        self::assertFalse($user->hasPassword());
    }

    public function testNormalisesNameAndEmail(): void
    {
        $user = new User('  Juan   Pérez  ', PhoneNumber::fromString('600123456'), [Role::Operator], ' Juan@Obra.ES ', ' ');

        self::assertSame('Juan Pérez', $user->getFullName());
        self::assertSame('juan@obra.es', $user->getEmail());
        self::assertNull($user->getOperatorNumber());
    }

    public function testNeedsAtLeastOneRole(): void
    {
        $this->expectException(ValidationFailed::class);

        new User('Juan Pérez', PhoneNumber::fromString('600123456'), []);
    }

    public function testHoldsSeveralRolesInAStableOrder(): void
    {
        $user = new User('Juan Pérez', PhoneNumber::fromString('600123456'), [Role::Operator, Role::Office]);

        self::assertSame(['ROLE_OFICINA', 'ROLE_OPERARIO'], $user->getRoles());
        self::assertFalse($user->isOnlyOperator());

        $user->replaceRoles([Role::Operator]);
        self::assertTrue($user->isOnlyOperator());
    }

    public function testActivationSetsThePasswordOnce(): void
    {
        $user = self::user();
        $user->activate('hash');

        self::assertSame(UserStatus::Active, $user->getStatus());
        self::assertSame('hash', $user->getPassword());

        $this->expectException(InvalidTransition::class);
        $user->activate('another');
    }

    public function testBlockUnblockDeactivateReadmit(): void
    {
        $user = self::activeUser();

        $user->block('  Se ha llevado la llave  ');
        self::assertSame(UserStatus::Blocked, $user->getStatus());
        self::assertSame('Se ha llevado la llave', $user->getStatusReason());

        $user->unblock();
        self::assertSame(UserStatus::Active, $user->getStatus());
        self::assertNull($user->getStatusReason());

        $user->deactivate('Fin de contrato');
        self::assertSame(UserStatus::Deactivated, $user->getStatus());

        $user->readmit();
        self::assertSame(UserStatus::Active, $user->getStatus());
    }

    public function testDeactivationNeedsAReason(): void
    {
        $user = self::activeUser();

        $this->expectException(ValidationFailed::class);
        $user->deactivate('   ');
    }

    public function testCannotBlockAPendingUser(): void
    {
        $this->expectException(InvalidTransition::class);

        self::user()->block(null);
    }

    public function testCannotDeactivateAPendingUser(): void
    {
        $this->expectException(InvalidTransition::class);

        self::user()->deactivate('Nunca llegó a entrar');
    }

    public function testFiveFailuresLockForFifteenMinutes(): void
    {
        $user = self::activeUser();
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');

        for ($i = 1; $i < User::MAX_FAILED_LOGINS; ++$i) {
            self::assertFalse($user->registerFailedLogin($now));
        }
        self::assertTrue($user->registerFailedLogin($now));

        self::assertEquals($now->modify('+15 minutes'), $user->getLockedUntil());
        self::assertTrue($user->isLockedAt($now->modify('+14 minutes')));
        self::assertFalse($user->isLockedAt($now->modify('+15 minutes')));
        self::assertSame(0, $user->getFailedLoginAttempts(), 'the counter restarts after locking');
    }

    public function testEachLockoutWithin24HoursDoublesUpToFourHours(): void
    {
        $user = self::activeUser();
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');

        $durations = [];
        for ($lockout = 0; $lockout < 6; ++$lockout) {
            for ($i = 0; $i < User::MAX_FAILED_LOGINS; ++$i) {
                $user->registerFailedLogin($now);
            }
            $lockedUntil = $user->getLockedUntil();
            self::assertNotNull($lockedUntil);
            $durations[] = ($lockedUntil->getTimestamp() - $now->getTimestamp()) / 60;
            $now = $lockedUntil;
        }

        self::assertSame([15, 30, 60, 120, 240, 240], $durations);
    }

    public function testALockoutMoreThan24HoursLaterStartsAgainAt15Minutes(): void
    {
        $user = self::activeUser();
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');
        for ($i = 0; $i < User::MAX_FAILED_LOGINS; ++$i) {
            $user->registerFailedLogin($now);
        }

        $nextDay = $now->modify('+25 hours');
        for ($i = 0; $i < User::MAX_FAILED_LOGINS; ++$i) {
            $user->registerFailedLogin($nextDay);
        }

        self::assertEquals($nextDay->modify('+15 minutes'), $user->getLockedUntil());
    }

    public function testSuccessfulLoginAndPasswordChangeResetTheCounter(): void
    {
        $user = self::activeUser();
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');

        $user->registerFailedLogin($now);
        $user->registerSuccessfulLogin($now);
        self::assertSame(0, $user->getFailedLoginAttempts());
        self::assertEquals($now, $user->getLastLoginAt());

        for ($i = 0; $i < User::MAX_FAILED_LOGINS; ++$i) {
            $user->registerFailedLogin($now);
        }
        $user->changePassword('new-hash');
        self::assertFalse($user->isLockedAt($now));
    }

    private static function user(): User
    {
        return new User('Juan Pérez', PhoneNumber::fromString('600123456'), [Role::Operator]);
    }

    private static function activeUser(): User
    {
        $user = self::user();
        $user->activate('hash');

        return $user;
    }
}
