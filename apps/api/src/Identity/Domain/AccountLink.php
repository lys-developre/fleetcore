<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Single-use link sent by SMS to set a password: first activation or recovery.
 * Table name as in modelo-datos-01; the type column tells both uses apart.
 */
#[ORM\Entity]
#[ORM\Table(name: 'activation_token')]
#[ORM\UniqueConstraint(name: 'uniq_activation_token_hash', columns: ['token_hash'])]
class AccountLink
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(length: 64)]
        private string $tokenHash,
        #[ORM\Column(length: 20, enumType: AccountLinkType::class)]
        private AccountLinkType $type,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
    ) {
        $this->id = Uuid::v7();
        $this->expiresAt = $createdAt->add($type->lifetime());
    }

    /**
     * @return array{link: self, plain: string}
     */
    public static function issue(User $user, AccountLinkType $type, \DateTimeImmutable $now): array
    {
        $secret = SecretToken::generate();

        return ['link' => new self($user, $secret['hash'], $type, $now), 'plain' => $secret['plain']];
    }

    public function isUsableAt(\DateTimeImmutable $now): bool
    {
        return null === $this->usedAt && $this->expiresAt > $now;
    }

    public function markUsed(\DateTimeImmutable $now): void
    {
        $this->usedAt = $now;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): AccountLinkType
    {
        return $this->type;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
