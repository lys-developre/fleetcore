<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine;

use App\Identity\Domain\AccessToken;
use App\Identity\Domain\AccessTokenRepository;
use App\Identity\Domain\SessionProfile;
use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AccessTokenRepository::class)]
final readonly class DoctrineAccessTokenRepository implements AccessTokenRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(AccessToken $token): void
    {
        $this->entityManager->persist($token);
    }

    public function findByHash(string $tokenHash): ?AccessToken
    {
        /** @var AccessToken|null $token */
        $token = $this->entityManager->createQueryBuilder()
            ->select('t', 'u')
            ->from(AccessToken::class, 't')
            ->join('t.user', 'u')
            ->where('t.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();

        return $token;
    }

    public function usableFor(User $user, \DateTimeImmutable $now, ?SessionProfile $profile = null): array
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(AccessToken::class, 't')
            ->where('t.user = :user')
            ->andWhere('t.revokedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('t.lastUsedAt', 'ASC');
        if (null !== $profile) {
            $query->andWhere('t.profile = :profile')->setParameter('profile', $profile);
        }

        /** @var list<AccessToken> $tokens */
        $tokens = $query->getQuery()->getResult();

        return $tokens;
    }

    public function revokeAllFor(User $user, \DateTimeImmutable $now): int
    {
        $tokens = $this->usableFor($user, $now);
        foreach ($tokens as $token) {
            $token->revoke($now);
        }

        return \count($tokens);
    }
}
