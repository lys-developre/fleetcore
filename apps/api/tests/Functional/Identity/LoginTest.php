<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Factory\UserFactory;
use App\Identity\Domain\Role;
use App\Identity\Domain\UserStatus;
use App\Tests\Support\ApiTestCase;

/** POST /api/auth/login, /api/auth/logout and the bearer token on later requests (ADR 002, formularios-05 6 y 7). */
final class LoginTest extends ApiTestCase
{
    public function testSignsInWithThePhoneAsTypedAndReturnsADeviceToken(): void
    {
        $user = UserFactory::new()->operator()->create(['fullName' => 'Juan Pérez', 'phone' => '+34600123456']);

        $json = $this->request('POST', '/api/auth/login', ['phone' => '600 12 34 56', 'password' => UserFactory::DEFAULT_PASSWORD, 'profile' => 'mobile']);

        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertIsString($json['token']);
        self::assertSame('mobile', $json['profile']);
        self::assertSame('2026-12-24T08:00:00+00:00', $json['expiresAt'], '30 days for mobile, in UTC');
        self::assertSame('Juan Pérez', $json['user']['fullName']);
        self::assertSame(['ROLE_OPERARIO'], $json['user']['roles']);
        self::assertArrayNotHasKey('password', $json['user']);
        self::assertArrayNotHasKey('passwordHash', $json['user']);

        $me = $this->request('GET', '/api/me', token: $json['token']);
        self::assertResponseIsSuccessful();
        self::assertIsArray($me);
        self::assertSame($user->getId()->toRfc4122(), $me['id']);
        self::assertCount(1, $this->auditEntries('auth.login'));
    }

    public function testDesktopSessionsLastEightHours(): void
    {
        UserFactory::new()->office()->create(['phone' => '+34600123456']);

        $json = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => UserFactory::DEFAULT_PASSWORD]);

        self::assertIsArray($json);
        self::assertSame('desktop', $json['profile']);
        self::assertSame('2026-11-24T16:00:00+00:00', $json['expiresAt']);
    }

    public function testWrongPasswordAndUnknownPhoneGetTheSameAnswer(): void
    {
        UserFactory::new()->create(['phone' => '+34600123456']);

        $wrongPassword = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => 'not-the-password']);
        self::assertResponseStatusCodeSame(401);
        $unknownPhone = $this->request('POST', '/api/auth/login', ['phone' => '699999999', 'password' => 'not-the-password']);
        self::assertResponseStatusCodeSame(401);

        self::assertSame(['error' => ['code' => 'invalid_credentials']], $wrongPassword);
        self::assertSame($wrongPassword, $unknownPhone);
    }

    public function testUsersPendingActivationCannotSignIn(): void
    {
        UserFactory::new()->pending()->create(['phone' => '+34600123456']);

        $json = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => UserFactory::DEFAULT_PASSWORD]);

        self::assertResponseStatusCodeSame(401);
        self::assertErrorCode('invalid_credentials', $json);
    }

    public function testBlockedAndDeactivatedUsersAreToldSoOnlyWithTheRightPassword(): void
    {
        UserFactory::new()->create(['phone' => '+34600000011', 'status' => UserStatus::Blocked]);
        UserFactory::new()->create(['phone' => '+34600000012', 'status' => UserStatus::Deactivated]);

        $this->request('POST', '/api/auth/login', ['phone' => '600000011', 'password' => 'not-the-password']);
        self::assertResponseStatusCodeSame(401);

        self::assertErrorCode('account_blocked', $this->request('POST', '/api/auth/login', ['phone' => '600000011', 'password' => UserFactory::DEFAULT_PASSWORD]));
        self::assertResponseStatusCodeSame(403);
        self::assertErrorCode('account_deactivated', $this->request('POST', '/api/auth/login', ['phone' => '600000012', 'password' => UserFactory::DEFAULT_PASSWORD]));
        self::assertResponseStatusCodeSame(403);
    }

    public function testTheFifthFailureLocksTheAccountWithRetryAfter(): void
    {
        $user = UserFactory::new()->create(['phone' => '+34600123456']);

        for ($i = 0; $i < 4; ++$i) {
            $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => 'not-the-password']);
            self::assertResponseStatusCodeSame(401);
        }
        $json = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => 'not-the-password']);

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHeaderSame('Retry-After', '900');
        self::assertErrorCode('too_many_attempts', $json);
        self::assertTrue($this->reload($user)->isLockedAt(new \DateTimeImmutable('2026-11-24 09:14:00 Europe/Madrid')));
        self::assertCount(5, $this->auditEntries('auth.login_failed'));
    }

    public function testValidatesThePayload(): void
    {
        $json = $this->request('POST', '/api/auth/login', ['phone' => '', 'profile' => 'tablet']);

        self::assertResponseStatusCodeSame(422);
        self::assertViolation('phone', 'required', $json);
        self::assertViolation('password', 'required', $json);
        self::assertViolation('profile', 'profile_invalid', $json);
    }

    public function testRequestsWithoutTokenAreRejected(): void
    {
        self::assertErrorCode('unauthenticated', $this->request('GET', '/api/me'));
        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownTokensAreRejected(): void
    {
        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: 'made-up-token-made-up-token-made-up'));
        self::assertResponseStatusCodeSame(401);
    }

    public function testIdleSessionsExpireAndActivityKeepsThemAlive(): void
    {
        $user = UserFactory::new()->office()->create();
        $token = $this->tokenFor($user);

        self::mockTime('+7 hours');
        $this->request('GET', '/api/me', token: $token);
        self::assertResponseIsSuccessful();

        self::mockTime('+7 hours');
        $this->request('GET', '/api/me', token: $token);
        self::assertResponseIsSuccessful('used 7 hours ago: still alive');

        self::mockTime('+8 hours');
        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $token));
    }

    public function testLogoutRevokesOnlyThisDevice(): void
    {
        $user = UserFactory::new()->office()->create();
        $laptop = $this->tokenFor($user);
        $desk = $this->tokenFor($user);

        $this->request('POST', '/api/auth/logout', token: $laptop);
        self::assertResponseStatusCodeSame(204);

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $laptop));
        $this->request('GET', '/api/me', token: $desk);
        self::assertResponseIsSuccessful();
    }

    public function testAFourthDesktopSignInClosesTheLeastRecentlyUsedSession(): void
    {
        UserFactory::new()->office()->create(['phone' => '+34600123456']);
        $tokens = [];
        for ($i = 0; $i < 4; ++$i) {
            self::mockTime('+1 minute');
            $json = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => UserFactory::DEFAULT_PASSWORD]);
            self::assertIsArray($json);
            $tokens[] = $json['token'];
        }

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $tokens[0]));
        foreach (\array_slice($tokens, 1) as $token) {
            $this->request('GET', '/api/me', token: $token);
            self::assertResponseIsSuccessful();
        }
    }

    public function testMobileAllowsTwoDevicesIndependentlyOfDesktop(): void
    {
        UserFactory::new()->with(['roles' => [Role::Office, Role::Operator]])->create(['phone' => '+34600123456']);
        $login = function (string $profile): string {
            self::mockTime('+1 minute');
            $json = $this->request('POST', '/api/auth/login', ['phone' => '600123456', 'password' => UserFactory::DEFAULT_PASSWORD, 'profile' => $profile]);
            self::assertIsArray($json);

            return $json['token'];
        };

        $desktop = $login('desktop');
        $firstPhone = $login('mobile');
        $login('mobile');
        $login('mobile');

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $firstPhone));
        $this->request('GET', '/api/me', token: $desktop);
        self::assertResponseIsSuccessful();
    }

    public function testBlockingClosesEverySession(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $operator = UserFactory::new()->operator()->create();
        $operatorToken = $this->tokenFor($operator);

        $this->request('POST', '/api/users/'.$operator->getId().'/block', ['reason' => 'Se ha llevado la llave'], $this->tokenFor($admin));
        self::assertResponseIsSuccessful();

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $operatorToken));
    }
}
