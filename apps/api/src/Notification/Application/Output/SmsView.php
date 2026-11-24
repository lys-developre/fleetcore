<?php

declare(strict_types=1);

namespace App\Notification\Application\Output;

use App\Notification\Domain\SmsMessage;

/** Sandbox inbox row: the SMS FleetCore would have sent. */
final readonly class SmsView
{
    public function __construct(
        public string $id,
        public string $phone,
        public string $type,
        public string $body,
        public string $status,
        public ?string $recipientName,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function from(SmsMessage $message): self
    {
        return new self(
            id: $message->getId()->toRfc4122(),
            phone: $message->getPhone()->value,
            type: $message->getType()->value,
            body: $message->getBody(),
            status: $message->getStatus()->value,
            recipientName: $message->getRecipientUser()?->getFullName(),
            createdAt: $message->getCreatedAt(),
        );
    }
}
