<?php

declare(strict_types=1);

namespace App\Notification\Domain;

enum SmsStatus: string
{
    /** Stored and waiting for the worker. */
    case Queued = 'queued';
    /** Handed to the provider, or kept in the sandbox. */
    case Sent = 'sent';
    case Failed = 'failed';
}
