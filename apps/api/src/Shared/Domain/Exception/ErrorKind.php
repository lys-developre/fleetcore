<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/** What kind of failure a DomainException is. Presentation maps each kind to an HTTP status. */
enum ErrorKind
{
    case Invalid;
    case NotFound;
    case Conflict;
    case Unauthenticated;
    case Forbidden;
    case Gone;
    case RateLimited;
}
