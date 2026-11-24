<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Presentation\Controller;

use App\Shared\Presentation\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function testAnswers200AndOkWhenTheDatabaseResponds(): void
    {
        $result = self::createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = self::createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $response = new HealthController($connection, 'test')();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 'ok', 'checks' => ['database' => 'ok']], $this->summary((string) $response->getContent()));
    }

    public function testAnswers503AndDegradedWhenTheDatabaseFails(): void
    {
        $connection = self::createStub(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \RuntimeException('connection refused'));

        $response = new HealthController($connection, 'prod')();

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(['status' => 'degraded', 'checks' => ['database' => 'unavailable']], $this->summary((string) $response->getContent()));
    }

    public function testDoesNotLeakTheDatabaseErrorMessage(): void
    {
        $connection = self::createStub(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \RuntimeException('password authentication failed for user "fleetcore"'));

        $response = new HealthController($connection, 'prod')();

        self::assertStringNotContainsString('password', (string) $response->getContent());
    }

    /**
     * @return array{status: mixed, checks: mixed}
     */
    private function summary(string $json): array
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        return ['status' => $payload['status'], 'checks' => $payload['checks']];
    }
}
