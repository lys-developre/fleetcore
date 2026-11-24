<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\AccountLink;
use App\Identity\Domain\AccountLinkRepository;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\Exception\InvalidAccountLink;
use App\Identity\Domain\SecretToken;
use App\Identity\Domain\UserStatus;
use App\Shared\Application\ClientContext;
use App\Shared\Domain\Exception\TooManyAttempts;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Resolves the token in an activation or recovery link. Throttled by IP: links are unguessable,
 * but nobody needs to try dozens of them.
 */
final readonly class AccountLinkResolver
{
    public function __construct(
        private AccountLinkRepository $links,
        private ClockInterface $clock,
        private RateLimiterFactoryInterface $accountLinkIpLimiter,
    ) {
    }

    /** @throws InvalidAccountLink when missing, expired, used, or the account is no longer in the right state */
    public function resolve(string $plainToken, AccountLinkType $type, ClientContext $client): AccountLink
    {
        $limit = $this->accountLinkIpLimiter->create($client->ip ?? 'unknown')->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyAttempts($limit->getRetryAfter());
        }

        $link = $this->links->findByHash(SecretToken::hash($plainToken));
        if (null === $link || $link->getType() !== $type || !$link->isUsableAt($this->clock->now())) {
            throw new InvalidAccountLink();
        }

        $expected = AccountLinkType::Activation === $type ? UserStatus::PendingActivation : UserStatus::Active;
        if ($link->getUser()->getStatus() !== $expected) {
            throw new InvalidAccountLink();
        }

        return $link;
    }
}
