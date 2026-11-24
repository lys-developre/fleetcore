<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Who did what, on what, from where. Insert only: a trigger rejects UPDATE and DELETE.
 * No foreign key to app_user on purpose: the trail must survive anything that happens to the rows it describes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_log_entity', columns: ['entity_type', 'entity_id', 'occurred_at'])]
#[ORM\Index(name: 'idx_audit_log_actor', columns: ['actor_id', 'occurred_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    /**
     * @param array<string, mixed>|null $dataBefore
     * @param array<string, mixed>|null $dataAfter
     */
    public function __construct(
        #[ORM\Column(length: 64)]
        private string $action,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
        private \DateTimeImmutable $occurredAt,
        #[ORM\Column(length: 10, enumType: AuditResult::class)]
        private AuditResult $result = AuditResult::Success,
        #[ORM\Column(type: UuidType::NAME, nullable: true)]
        private ?Uuid $actorId = null,
        #[ORM\Column(length: 64, nullable: true)]
        private ?string $entityType = null,
        #[ORM\Column(type: UuidType::NAME, nullable: true)]
        private ?Uuid $entityId = null,
        #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
        private ?array $dataBefore = null,
        #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
        private ?array $dataAfter = null,
        #[ORM\Column(length: 45, nullable: true)]
        private ?string $ip = null,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $userAgent = null,
    ) {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResult(): AuditResult
    {
        return $this->result;
    }

    public function getActorId(): ?Uuid
    {
        return $this->actorId;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?Uuid
    {
        return $this->entityId;
    }

    /** @return array<string, mixed>|null */
    public function getDataBefore(): ?array
    {
        return $this->dataBefore;
    }

    /** @return array<string, mixed>|null */
    public function getDataAfter(): ?array
    {
        return $this->dataAfter;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
}
