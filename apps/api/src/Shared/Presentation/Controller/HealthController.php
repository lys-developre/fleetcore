<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Liveness and readiness probe. Used by Caddy, CI smoke tests and the web app's status page.
 */
#[AsController]
final class HealthController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $appEnv,
    ) {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
        } catch (\Throwable) {
            $database = 'unavailable';
        }

        $status = 'ok' === $database ? 'ok' : 'degraded';

        return new JsonResponse(
            [
                'status' => $status,
                'service' => 'fleetcore-api',
                'env' => $this->appEnv,
                'checks' => ['database' => $database],
                'time' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            'ok' === $status ? 200 : 503,
        );
    }
}
