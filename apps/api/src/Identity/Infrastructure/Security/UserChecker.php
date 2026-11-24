<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Defence in depth: blocking or deactivating revokes every token already, but a token never authenticates
 * a user who is not active.
 */
final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && UserStatus::Active !== $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('Account is not active.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
