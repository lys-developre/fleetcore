<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/** Rate limit or temporary lockout. HTTP 429 with Retry-After. */
final class TooManyAttempts extends DomainException
{
    public function __construct(public readonly \DateTimeImmutable $retryAfter)
    {
        parent::__construct('too_many_attempts', 'Too many attempts, try again later.', [
            'retryAfter' => $retryAfter->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::RateLimited;
    }
}
