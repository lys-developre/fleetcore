<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/** A rule that protects the integrity of the data, not tied to a form field. HTTP 409. */
final class BusinessRuleViolation extends DomainException
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
