<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointAnswersWithServiceStatus(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/health');

        $response = $client->getResponse();
        self::assertContains($response->getStatusCode(), [200, 503]);
        self::assertResponseHeaderSame('content-type', 'application/json');

        /** @var array{status: string, service: string, checks: array{database: string}} $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('fleetcore-api', $payload['service']);
        self::assertContains($payload['status'], ['ok', 'degraded']);
        self::assertArrayHasKey('database', $payload['checks']);
    }
}
