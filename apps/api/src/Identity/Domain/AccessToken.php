<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * One opaque token per signed-in device (ADR 002). Expiry slides with use; revoking is setting revokedAt,
 * effective on the device's next request.
 */
#[ORM\Entity]
#[ORM\Table(name: 'access_token')]
#[ORM\UniqueConstraint(name: 'uniq_access_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_access_token_user_revoked', columns: ['user_id', 'revoked_at'])]
class AccessToken
{
    /** Writing on every request is wasteful: last use is recorded at most once a minute. */
    private const int TOUCH_INTERVAL_SECONDS = 60;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $lastUsedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(length: 64)]
        private string $tokenHash,
        #[ORM\Column(length: 10, enumType: SessionProfile::class)]
        private SessionProfile $profile,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $userAgent,
        #[ORM\Column(length: 45, nullable: true)]
        private ?string $ip,
    ) {
        $this->id = Uuid::v7();
        $this->lastUsedAt = $createdAt;
        $this->expiresAt = $createdAt->add($profile->idleTimeout());
    }

    /**
     * @return array{token: self, plain: string} the plain token goes to the device once and is never stored
     */
    public static function issue(User $user, SessionProfile $profile, \DateTimeImmutable $now, ?string $userAgent = null, ?string $ip = null): array
    {
        $secret = SecretToken::generate();

        return [
            'token' => new self($user, $secret['hash'], $profile, $now, $userAgent, $ip),
            'plain' => $secret['plain'],
        ];
    }

    public function isUsableAt(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && $this->expiresAt > $now;
    }

    /** @return bool whether anything changed and needs to be written */
    public function touch(\DateTimeImmutable $now): bool
    {
        if ($now->getTimestamp() - $this->lastUsedAt->getTimestamp() < self::TOUCH_INTERVAL_SECONDS) {
            return false;
        }
        $this->lastUsedAt = $now;
        $this->expiresAt = $now->add($this->profile->idleTimeout());

        return true;
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProfile(): SessionProfile
    {
        return $this->profile;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): \DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
}
