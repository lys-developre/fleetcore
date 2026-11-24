<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\SecretToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * `Authorization: Bearer <token>` → user (ADR 002, arquitectura 3.2 paso 2). Looks the token up by hash,
 * rejects revoked or expired ones and slides the expiry forward.
 */
final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenRepository $tokens,
        private CurrentSession $currentSession,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $now = $this->clock->now();
        $token = $this->tokens->findByHash(SecretToken::hash($accessToken));
        if (null === $token || !$token->isUsableAt($now)) {
            throw new BadCredentialsException('Invalid or expired session.');
        }

        if ($token->touch($now)) {
            $this->entityManager->flush();
        }
        $this->currentSession->set($token);

        $user = $token->getUser();

        return new UserBadge($user->getUserIdentifier(), static fn () => $user);
    }
}
