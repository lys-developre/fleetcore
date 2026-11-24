<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Symfony\Component\Uid\Uuid;

/** Entities using the RowAudit trait. */
interface RowAudited
{
    public function stampCreation(\DateTimeImmutable $now, ?Uuid $by): void;

    public function stampUpdate(\DateTimeImmutable $now, ?Uuid $by): void;
}
