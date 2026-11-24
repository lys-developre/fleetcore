<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\AccessToken;
use App\Identity\Domain\AccountLink;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\Role;
use App\Identity\Domain\SecretToken;
use App\Identity\Domain\SessionProfile;
use App\Identity\Domain\User;
use App\Shared\Domain\PhoneNumber;
use PHPUnit\Framework\TestCase;

/** ADR 002 and formularios-05, secciones 6.2, 6.5 y 7. */
final class TokensTest extends TestCase
{
    public function testDesktopSessionsLastEightIdleHoursAndMobileThirtyDays(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');

        $desktop = AccessToken::issue(self::user(), SessionProfile::Desktop, $now)['token'];
        $mobile = AccessToken::issue(self::user(), SessionProfile::Mobile, $now)['token'];

        self::assertEquals($now->modify('+8 hours'), $desktop->getExpiresAt());
        self::assertEquals($now->modify('+30 days'), $mobile->getExpiresAt());
        self::assertSame(3, SessionProfile::Desktop->maxDevices());
        self::assertSame(2, SessionProfile::Mobile->maxDevices());
    }

    public function testUseSlidesTheExpiryForwardAtMostOncePerMinute(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');
        $token = AccessToken::issue(self::user(), SessionProfile::Desktop, $now)['token'];

        self::assertFalse($token->touch($now->modify('+30 seconds')));
        self::assertEquals($now->modify('+8 hours'), $token->getExpiresAt());

        self::assertTrue($token->touch($now->modify('+7 hours')));
        self::assertEquals($now->modify('+15 hours'), $token->getExpiresAt());
    }

    public function testExpiredOrRevokedTokensAreNotUsable(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');
        $token = AccessToken::issue(self::user(), SessionProfile::Desktop, $now)['token'];

        self::assertTrue($token->isUsableAt($now->modify('+7 hours')));
        self::assertFalse($token->isUsableAt($now->modify('+8 hours')));

        $token->revoke($now);
        self::assertFalse($token->isUsableAt($now));
    }

    public function testThePlainTokenIsRandomAndOnlyItsHashIsKept(): void
    {
        $first = SecretToken::generate();
        $second = SecretToken::generate();

        self::assertNotSame($first['plain'], $second['plain']);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $first['plain']);
        self::assertSame(hash('sha256', $first['plain']), $first['hash']);
    }

    public function testActivationLinksLast72HoursAndRecoveryLinks30Minutes(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');

        $activation = AccountLink::issue(self::user(), AccountLinkType::Activation, $now)['link'];
        $reset = AccountLink::issue(self::user(), AccountLinkType::PasswordReset, $now)['link'];

        self::assertEquals($now->modify('+72 hours'), $activation->getExpiresAt());
        self::assertEquals($now->modify('+30 minutes'), $reset->getExpiresAt());
    }

    public function testALinkWorksOnce(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 08:00:00');
        $link = AccountLink::issue(self::user(), AccountLinkType::Activation, $now)['link'];

        self::assertTrue($link->isUsableAt($now));
        $link->markUsed($now);
        self::assertFalse($link->isUsableAt($now));
    }

    private static function user(): User
    {
        return new User('Juan Pérez', PhoneNumber::fromString('600123456'), [Role::Operator]);
    }
}
