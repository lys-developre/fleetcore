<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/** A state change that the current state does not allow. HTTP 409. */
final class InvalidTransition extends DomainException
{
    public static function because(string $errorCode, string $message): self
    {
        return new self($errorCode, $message);
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::Conflict;
    }
}
