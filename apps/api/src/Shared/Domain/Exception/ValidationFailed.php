<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Field-level errors found by a use case (uniqueness, password policy): same 422 shape as the
 * Validator errors on request DTOs, so the web app maps both to the form the same way.
 */
final class ValidationFailed extends DomainException
{
    /**
     * @param list<array{field: string, code: string}> $violations
     */
    private function __construct(public readonly array $violations)
    {
        parent::__construct('validation_failed', 'The submitted data is not valid.');
    }

    public static function forField(string $field, string $code): self
    {
        return new self([['field' => $field, 'code' => $code]]);
    }

    /**
     * @param list<array{field: string, code: string}> $violations
     */
    public static function withViolations(array $violations): self
    {
        return new self($violations);
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::Invalid;
    }
}
