<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/** HTTP 404. Route parameters resolved by MapEntity already answer 404 on their own. */
final class NotFound extends DomainException
{
    public static function because(string $errorCode, string $message): self
    {
        return new self($errorCode, $message);
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::NotFound;
    }
}
