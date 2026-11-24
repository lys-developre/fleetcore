<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Factory\UserFactory;
use App\Identity\Domain\SessionProfile;
use App\Tests\Support\ApiTestCase;

/** /api/me: own data and own devices (matriz 5.4, columna "P"; sección 7, cierre remoto por el propio operario). */
final class MeTest extends ApiTestCase
{
    public function testListsTheOpenSessionsMarkingThisOne(): void
    {
        $user = UserFactory::new()->operator()->create();
        $this->tokenFor($user, SessionProfile::Mobile);
        self::mockTime('+1 minute');
        $current = $this->tokenFor($user, SessionProfile::Mobile);

        $json = $this->request('GET', '/api/me/sessions', token: $current, userAgent: 'Mozilla/5.0 (Linux; Android 14)');

        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertCount(2, $json);
        self::assertTrue($json[0]['current'], 'most recent first');
        self::assertFalse($json[1]['current']);
        self::assertSame('mobile', $json[1]['profile']);
    }

    public function testAnOperatorSignsOutThePhoneLeftInAnotherCab(): void
    {
        $user = UserFactory::new()->operator()->create();
        $lostPhone = $this->tokenFor($user, SessionProfile::Mobile);
        $current = $this->tokenFor($user, SessionProfile::Mobile);

        $sessions = $this->request('GET', '/api/me/sessions', token: $current);
        self::assertIsArray($sessions);
        $lost = array_values(array_filter($sessions, static fn (array $session): bool => !$session['current']))[0];

        $this->request('DELETE', '/api/me/sessions/'.$lost['id'], token: $current);
        self::assertResponseStatusCodeSame(204);

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $lostPhone));
        $this->request('GET', '/api/me', token: $current);
        self::assertResponseIsSuccessful();
    }

    public function testSomeoneElsesSessionIsNotFound(): void
    {
        $user = UserFactory::new()->operator()->create();
        $other = UserFactory::new()->operator()->create();
        $this->tokenFor($other);
        $theirs = $this->request('GET', '/api/me/sessions', token: $this->tokenFor($other));
        self::assertIsArray($theirs);

        $this->request('DELETE', '/api/me/sessions/'.$theirs[0]['id'], token: $this->tokenFor($user));

        self::assertResponseStatusCodeSame(404);
    }

    public function testViewersCannotCloseSessionsRemotely(): void
    {
        $viewer = UserFactory::new()->viewer()->create();
        $token = $this->tokenFor($viewer);
        $sessions = $this->request('GET', '/api/me/sessions', token: $token);
        self::assertIsArray($sessions);

        $this->request('DELETE', '/api/me/sessions/'.$sessions[0]['id'], token: $token);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUsersChangeTheirOwnEmail(): void
    {
        $user = UserFactory::new()->operator()->create();
        UserFactory::new()->create(['email' => 'ocupado@obra.es']);
        $token = $this->tokenFor($user);

        $json = $this->request('PATCH', '/api/me', ['email' => 'Juan@Obra.es'], $token);
        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertSame('juan@obra.es', $json['email']);

        self::assertViolation('email', 'email_taken', $this->request('PATCH', '/api/me', ['email' => 'ocupado@obra.es'], $token));
    }
}
