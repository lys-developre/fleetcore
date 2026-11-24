<?php

declare(strict_types=1);

namespace App\Identity\Application\Output;

/**
 * Answer to a successful sign-in, activation or password reset. `token` is shown once: the device keeps it,
 * the server only has its hash.
 */
final readonly class IssuedSession
{
    public function __construct(
        public string $token,
        public string $profile,
        public \DateTimeImmutable $expiresAt,
        public UserView $user,
    ) {
    }
}
