<?php

declare(strict_types=1);

namespace App\Notification\Domain;

use App\Shared\Domain\PhoneNumber;

/** The one seam to the SMS provider. Swapping Twilio for another provider touches nothing else. */
interface SmsSender
{
    /**
     * @return string the provider's message id
     *
     * @throws SmsDeliveryFailed
     */
    public function send(PhoneNumber $to, string $body): string;

    /** Stored on each message: "sandbox", "twilio". */
    public function name(): string;
}
