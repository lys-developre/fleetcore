<?php

declare(strict_types=1);

namespace App\Identity\Domain;

interface AccessTokenRepository
{
    public function add(AccessToken $token): void;

    public function findByHash(string $tokenHash): ?AccessToken;

    /** @return list<AccessToken> usable at $now, least recently used first */
    public function usableFor(User $user, \DateTimeImmutable $now, ?SessionProfile $profile = null): array;

    /** @return int how many were revoked */
    public function revokeAllFor(User $user, \DateTimeImmutable $now): int;
}
