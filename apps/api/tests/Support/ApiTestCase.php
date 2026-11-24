<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Identity\Domain\AccessToken;
use App\Identity\Domain\SessionProfile;
use App\Identity\Domain\User;
use App\Notification\Domain\SmsMessage;
use App\Shared\Domain\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Foundry\Test\Factories;

/**
 * Base for HTTP tests of the API: JSON requests, bearer tokens issued straight into the database, and
 * fresh reads after a request (the kernel reboots between requests, so earlier entity instances go stale).
 */
abstract class ApiTestCase extends WebTestCase
{
    use ClockSensitiveTrait;
    use Factories;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::mockTime('2026-11-24 09:00:00 Europe/Madrid');
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<mixed>|null decoded JSON body, null when empty
     */
    protected function request(string $method, string $uri, ?array $body = null, ?string $token = null, ?string $userAgent = null): ?array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        if (null !== $userAgent) {
            $server['HTTP_USER_AGENT'] = $userAgent;
        }

        $this->client->request($method, $uri, server: $server, content: null === $body ? null : json_encode($body, \JSON_THROW_ON_ERROR));
        $content = (string) $this->client->getResponse()->getContent();

        /** @var array<mixed>|null $decoded */
        $decoded = '' === $content ? null : json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /** A valid device token for $user, without going through the login endpoint. */
    protected function tokenFor(User $user, SessionProfile $profile = SessionProfile::Desktop): string
    {
        $entityManager = $this->entityManager();
        $managed = $entityManager->find(User::class, $user->getId());
        self::assertNotNull($managed);

        ['token' => $token, 'plain' => $plain] = AccessToken::issue($managed, $profile, Clock::get()->now());
        $entityManager->persist($token);
        $entityManager->flush();

        return $plain;
    }

    protected function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /** Re-read a user after requests. */
    protected function reload(User $user): User
    {
        $entityManager = $this->entityManager();
        $entityManager->clear();
        $fresh = $entityManager->find(User::class, $user->getId());
        self::assertNotNull($fresh);

        return $fresh;
    }

    /** @return list<SmsMessage> oldest first */
    protected function smsTo(string $phone): array
    {
        return $this->entityManager()->getRepository(SmsMessage::class)->findBy(['phone' => $phone], ['createdAt' => 'ASC']);
    }

    /** The one-time token at the end of the link in an SMS. */
    protected function linkTokenIn(SmsMessage $message): string
    {
        self::assertSame(1, preg_match('#/(?:activate|reset-password)/([A-Za-z0-9_-]+)#', $message->getBody(), $match), $message->getBody());

        return $match[1];
    }

    /** @return list<AuditLog> */
    protected function auditEntries(string $action): array
    {
        return $this->entityManager()->getRepository(AuditLog::class)->findBy(['action' => $action]);
    }

    /**
     * @param array<mixed>|null $json
     */
    protected static function assertErrorCode(string $code, ?array $json): void
    {
        self::assertIsArray($json);
        self::assertIsArray($json['error'] ?? null, (string) json_encode($json));
        self::assertSame($code, $json['error']['code'] ?? null, (string) json_encode($json));
    }

    /**
     * @param array<mixed>|null $json
     */
    protected static function assertViolation(string $field, string $code, ?array $json): void
    {
        self::assertErrorCode('validation_failed', $json);
        self::assertIsArray($json);
        self::assertContains(['field' => $field, 'code' => $code], $json['error']['violations'] ?? [], (string) json_encode($json));
    }
}
