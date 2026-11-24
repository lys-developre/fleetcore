<?php

declare(strict_types=1);

namespace App\Notification\Domain;

/** Why an SMS was sent (modelo-datos-01, sms_message.tipo). Signing types arrive with H5 and H6. */
enum SmsType: string
{
    case Activation = 'activation';
    case PasswordReset = 'password_reset';
}
