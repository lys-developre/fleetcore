<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/** What a one-time SMS link is for, and how long it lives (formularios-05, secciones 6.2 y 6.5). */
enum AccountLinkType: string
{
    case Activation = 'activation';
    case PasswordReset = 'password_reset';

    public function lifetime(): \DateInterval
    {
        return match ($this) {
            self::Activation => new \DateInterval('PT72H'),
            self::PasswordReset => new \DateInterval('PT30M'),
        };
    }
}
