<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Base of every business error. `errorCode` is a stable snake_case identifier the web app maps to
 * plain-language Spanish messages; the PHP message is English and only meant for logs.
 * The HTTP status follows from kind(), mapped in App\Shared\Presentation\EventListener\ApiExceptionListener.
 */
abstract class DomainException extends \RuntimeException
{
    /**
     * @param array<string, scalar|null> $context extra, non-sensitive data exposed to the client
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    abstract public function kind(): ErrorKind;
}
