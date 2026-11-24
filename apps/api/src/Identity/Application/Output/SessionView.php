<?php

declare(strict_types=1);

namespace App\Identity\Application\Output;

use App\Identity\Domain\AccessToken;

/** A signed-in device, for "my sessions" and remote sign-out. */
final readonly class SessionView
{
    public function __construct(
        public string $id,
        public string $profile,
        public ?string $userAgent,
        public ?string $ip,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $lastUsedAt,
        public \DateTimeImmutable $expiresAt,
        public bool $current,
    ) {
    }

    public static function from(AccessToken $token, ?AccessToken $current): self
    {
        return new self(
            id: $token->getId()->toRfc4122(),
            profile: $token->getProfile()->value,
            userAgent: $token->getUserAgent(),
            ip: $token->getIp(),
            createdAt: $token->getCreatedAt(),
            lastUsedAt: $token->getLastUsedAt(),
            expiresAt: $token->getExpiresAt(),
            current: null !== $current && $current->getId()->equals($token->getId()),
        );
    }
}
