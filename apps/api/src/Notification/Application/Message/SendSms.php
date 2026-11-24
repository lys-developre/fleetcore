<?php

declare(strict_types=1);

namespace App\Notification\Application\Message;

use Symfony\Component\Uid\Uuid;

/** Deliver a stored SmsMessage. Handled by the worker, off the request. */
final readonly class SendSms
{
    public function __construct(public Uuid $smsMessageId)
    {
    }
}
