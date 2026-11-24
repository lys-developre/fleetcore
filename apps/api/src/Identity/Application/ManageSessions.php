<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Output\SessionView;
use App\Identity\Domain\AccessToken;
use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\User;
use App\Shared\Application\AuditTrail;
use App\Shared\Domain\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/** Signed-in devices and remote sign-out (formularios-05, sección 7). */
final readonly class ManageSessions
{
    public function __construct(
        private AccessTokenRepository $tokens,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<SessionView> most recently used first */
    public function list(User $user, ?AccessToken $current = null): array
    {
        $views = array_map(
            static fn (AccessToken $token): SessionView => SessionView::from($token, $current),
            $this->tokens->usableFor($user, $this->clock->now()),
        );

        return array_reverse($views);
    }

    /** @return int sessions closed */
    public function revokeAll(User $target): int
    {
        $count = $this->tokens->revokeAllFor($target, $this->clock->now());
        $this->audit->record('user.sessions_revoked', 'user', $target->getId(), after: ['count' => $count]);
        $this->entityManager->flush();

        return $count;
    }

    /** Signing out one of your own devices, e.g. the phone left in another cab. */
    public function revokeOwn(User $owner, Uuid $sessionId): void
    {
        foreach ($this->tokens->usableFor($owner, $this->clock->now()) as $token) {
            if ($token->getId()->equals($sessionId)) {
                $token->revoke($this->clock->now());
                $this->audit->record('user.session_revoked', 'user', $owner->getId(), after: ['session' => $sessionId->toRfc4122()]);
                $this->entityManager->flush();

                return;
            }
        }

        throw NotFound::because('session_not_found', 'No such open session.');
    }
}
