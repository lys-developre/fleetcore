<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Output\IssuedSession;
use App\Identity\Application\Output\UserView;
use App\Identity\Domain\AccessToken;
use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\SessionProfile;
use App\Identity\Domain\User;
use App\Shared\Application\ClientContext;
use Symfony\Component\Clock\ClockInterface;

/**
 * Hands a new device token to a user who just proved who they are (password, activation or reset link).
 * Over the device limit of the profile, the least recently used session is closed.
 */
final readonly class SessionIssuer
{
    public function __construct(
        private AccessTokenRepository $tokens,
        private ClockInterface $clock,
    ) {
    }

    public function issue(User $user, SessionProfile $profile, ClientContext $client): IssuedSession
    {
        $now = $this->clock->now();

        $open = $this->tokens->usableFor($user, $now, $profile);
        $excess = \count($open) - ($profile->maxDevices() - 1);
        foreach (\array_slice($open, 0, max(0, $excess)) as $oldest) {
            $oldest->revoke($now);
        }

        ['token' => $token, 'plain' => $plain] = AccessToken::issue($user, $profile, $now, $client->userAgent, $client->ip);
        $this->tokens->add($token);
        $user->registerSuccessfulLogin($now);

        return new IssuedSession($plain, $profile->value, $token->getExpiresAt(), UserView::from($user));
    }
}
