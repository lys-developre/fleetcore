<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine;

use App\Identity\Domain\AccountLink;
use App\Identity\Domain\AccountLinkRepository;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AccountLinkRepository::class)]
final readonly class DoctrineAccountLinkRepository implements AccountLinkRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(AccountLink $link): void
    {
        $this->entityManager->persist($link);
    }

    public function findByHash(string $tokenHash): ?AccountLink
    {
        return $this->entityManager->getRepository(AccountLink::class)->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function invalidateFor(User $user, AccountLinkType $type, \DateTimeImmutable $now): void
    {
        $links = $this->entityManager->getRepository(AccountLink::class)->findBy(['user' => $user, 'type' => $type, 'usedAt' => null]);
        foreach ($links as $link) {
            $link->markUsed($now);
        }
    }
}
