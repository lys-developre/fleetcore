<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sms;

use App\Notification\Domain\SmsSender;
use App\Shared\Domain\PhoneNumber;
use Symfony\Component\Uid\Uuid;

/**
 * Sends nothing. The message is already stored in sms_message, and the office reads it in the sandbox inbox:
 * the demo costs nothing and shows the whole flow on screen.
 */
final class SandboxSmsSender implements SmsSender
{
    public function send(PhoneNumber $to, string $body): string
    {
        return 'sandbox-'.Uuid::v7()->toRfc4122();
    }

    public function name(): string
    {
        return 'sandbox';
    }
}
