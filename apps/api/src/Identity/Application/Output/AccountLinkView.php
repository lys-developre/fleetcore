<?php

declare(strict_types=1);

namespace App\Identity\Application\Output;

/** What the activation and reset pages show before asking for the password: "Hola, Juan". */
final readonly class AccountLinkView
{
    public function __construct(
        public string $type,
        public string $fullName,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
