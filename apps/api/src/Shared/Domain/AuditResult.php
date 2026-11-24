<?php

declare(strict_types=1);

namespace App\Shared\Domain;

enum AuditResult: string
{
    case Success = 'success';
    /** The action was attempted and refused: wrong password, voter denial. */
    case Denied = 'denied';
}
