<?php

declare(strict_types=1);

namespace App\Identity\Domain;

interface AccountLinkRepository
{
    public function add(AccountLink $link): void;

    public function findByHash(string $tokenHash): ?AccountLink;

    /** Marks every unused link of that type as used: only the latest SMS works. */
    public function invalidateFor(User $user, AccountLinkType $type, \DateTimeImmutable $now): void;
}
