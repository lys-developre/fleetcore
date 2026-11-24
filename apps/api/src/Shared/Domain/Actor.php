<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Symfony\Component\Uid\Uuid;

/**
 * Who is acting. Implemented by the Identity user so Shared (audit, row stamps) never depends on Identity.
 */
interface Actor
{
    public function getId(): Uuid;
}
