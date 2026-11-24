<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/** formularios-05, sección 3.2. The temporary lock after failed logins is not a status: see User::$lockedUntil. */
enum UserStatus: string
{
    case PendingActivation = 'pending_activation';
    case Active = 'active';
    case Blocked = 'blocked';
    case Deactivated = 'deactivated';
}
