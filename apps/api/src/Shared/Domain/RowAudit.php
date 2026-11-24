<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * created_at / updated_at / created_by / updated_by on every table users edit (modelo-datos-01, sección 1).
 * Filled by App\Shared\Infrastructure\Doctrine\RowAuditListener; entities never set them by hand.
 */
trait RowAudit
{
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $createdBy = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $updatedBy = null;

    public function stampCreation(\DateTimeImmutable $now, ?Uuid $by): void
    {
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->createdBy = $by;
        $this->updatedBy = $by;
    }

    public function stampUpdate(\DateTimeImmutable $now, ?Uuid $by): void
    {
        $this->updatedAt = $now;
        $this->updatedBy = $by;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
