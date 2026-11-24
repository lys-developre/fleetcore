<?php

declare(strict_types=1);

namespace App\Notification\Domain;

use App\Identity\Domain\User;
use App\Shared\Domain\PhoneNumber;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Every SMS FleetCore sends, with its text: audit of what was said to whom, and the sandbox inbox
 * when SMS_DRIVER=sandbox. Contact recipients (foremen) are added with the signing flow.
 */
#[ORM\Entity]
#[ORM\Table(name: 'sms_message')]
#[ORM\Index(name: 'idx_sms_message_created_at', columns: ['created_at'])]
class SmsMessage
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column(length: 10, enumType: SmsStatus::class)]
    private SmsStatus $status = SmsStatus::Queued;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $driver = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct(
        PhoneNumber $phone,
        #[ORM\Column(length: 20, enumType: SmsType::class)]
        private SmsType $type,
        #[ORM\Column(type: Types::TEXT)]
        private string $body,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?User $recipientUser = null,
    ) {
        $this->id = Uuid::v7();
        $this->phone = $phone->value;
    }

    public function markSent(string $driver, string $externalId, \DateTimeImmutable $now): void
    {
        $this->status = SmsStatus::Sent;
        $this->driver = $driver;
        $this->externalId = mb_substr($externalId, 0, 64);
        $this->error = null;
        $this->sentAt = $now;
    }

    public function markFailed(string $driver, string $error): void
    {
        $this->status = SmsStatus::Failed;
        $this->driver = $driver;
        $this->error = mb_substr($error, 0, 255);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPhone(): PhoneNumber
    {
        return PhoneNumber::fromString($this->phone);
    }

    public function getType(): SmsType
    {
        return $this->type;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatus(): SmsStatus
    {
        return $this->status;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getRecipientUser(): ?User
    {
        return $this->recipientUser;
    }
}
