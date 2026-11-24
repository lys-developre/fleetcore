<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Contract of GET /api/health as consumed by Caddy, the deploy script and the web status page.
 * The test database is always available here; the degraded branch is covered by the unit test.
 */
final class HealthEndpointTest extends WebTestCase
{
    public function testReportsOkWhenTheDatabaseAnswers(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = $this->decode((string) $client->getResponse()->getContent());
        self::assertSame('ok', $payload['status']);
        self::assertSame('fleetcore-api', $payload['service']);
        self::assertSame('test', $payload['env']);
        self::assertSame(['database' => 'ok'], $payload['checks']);
        self::assertNotFalse(
            \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $payload['time']),
            'time must be an ATOM (ISO 8601) timestamp',
        );
    }

    public function testOnlyAcceptsGet(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/health');

        self::assertResponseStatusCodeSame(405);
    }

    /**
     * @return array{status: string, service: string, env: string, checks: array<string, string>, time: string}
     */
    private function decode(string $json): array
    {
        /** @var array{status: string, service: string, env: string, checks: array<string, string>, time: string} $payload */
        $payload = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        return $payload;
    }
}
