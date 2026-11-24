<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Identity\Domain\UserRole;
use App\Identity\Domain\UserStatus;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserRepository::class)]
final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(User $user): void
    {
        $this->entityManager->persist($user);
    }

    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
    }

    public function findByPhone(PhoneNumber $phone): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['mobilePhone' => $phone->value]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    public function findByOperatorNumber(string $operatorNumber): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['operatorNumber' => trim($operatorNumber)]);
    }

    public function all(): array
    {
        /** @var list<User> $users */
        $users = $this->entityManager->createQueryBuilder()
            ->select('u', 'r')
            ->from(User::class, 'u')
            ->leftJoin('u.roles', 'r')
            ->orderBy('u.fullName', 'ASC')
            ->getQuery()
            ->getResult();

        return $users;
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT u.id)')
            ->from(User::class, 'u')
            ->join(UserRole::class, 'r', 'WITH', 'r.user = u')
            ->where('u.status = :active')
            ->andWhere('r.role = :admin')
            ->setParameter('active', UserStatus::Active)
            ->setParameter('admin', Role::Admin)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
