<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Domain\Actor;
use App\Shared\Domain\AuditLog;
use App\Shared\Domain\AuditResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Writes audit_log rows. The entry is persisted, not flushed: it commits together with the change it describes.
 * Actor, IP and user agent come from the current request unless given.
 */
final readonly class AuditTrail
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RequestStack $requestStack,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function record(
        string $action,
        ?string $entityType = null,
        ?Uuid $entityId = null,
        ?array $before = null,
        ?array $after = null,
        AuditResult $result = AuditResult::Success,
        ?Actor $actor = null,
    ): void {
        $actor ??= $this->currentActor();
        $request = $this->requestStack->getMainRequest();
        $userAgent = $request?->headers->get('User-Agent');

        $this->entityManager->persist(new AuditLog(
            action: $action,
            occurredAt: $this->clock->now(),
            result: $result,
            actorId: $actor?->getId(),
            entityType: $entityType,
            entityId: $entityId,
            dataBefore: $before,
            dataAfter: $after,
            ip: $request?->getClientIp(),
            userAgent: null === $userAgent ? null : mb_substr($userAgent, 0, 255),
        ));
    }

    private function currentActor(): ?Actor
    {
        $user = $this->security->getUser();

        return $user instanceof Actor ? $user : null;
    }
}
