<?php

declare(strict_types=1);

namespace App\Tests\Functional\Notification;

use App\Factory\UserFactory;
use App\Tests\Support\ApiTestCase;

/** GET /api/sms-sandbox: what FleetCore would have texted, for office and the demo. */
final class SandboxInboxTest extends ApiTestCase
{
    public function testOfficeReadsTheSmsSentInSandbox(): void
    {
        $office = $this->tokenFor(UserFactory::new()->office()->create());
        $this->request('POST', '/api/users', ['fullName' => 'Juan Pérez', 'phone' => '611223344', 'roles' => ['ROLE_OPERARIO']], $office);

        $json = $this->request('GET', '/api/sms-sandbox', token: $office);

        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertCount(1, $json);
        self::assertSame('+34611223344', $json[0]['phone']);
        self::assertSame('activation', $json[0]['type']);
        self::assertSame('queued', $json[0]['status']);
        self::assertSame('Juan Pérez', $json[0]['recipientName']);
        self::assertStringContainsString('/activate/', $json[0]['body']);
    }

    public function testOperatorsAndViewersDoNotSeeIt(): void
    {
        $this->request('GET', '/api/sms-sandbox', token: $this->tokenFor(UserFactory::new()->operator()->create()));
        self::assertResponseStatusCodeSame(403);

        $this->request('GET', '/api/sms-sandbox', token: $this->tokenFor(UserFactory::new()->viewer()->create()));
        self::assertResponseStatusCodeSame(403);
    }
}
