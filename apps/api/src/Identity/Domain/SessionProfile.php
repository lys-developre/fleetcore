<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/**
 * Session profile, decided by the interface the user signs in from, not by the role (formularios-05, sección 7).
 */
enum SessionProfile: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';

    /** Inactivity after which the token expires. Every authenticated request pushes it forward. */
    public function idleTimeout(): \DateInterval
    {
        return match ($this) {
            self::Desktop => new \DateInterval('PT8H'),
            self::Mobile => new \DateInterval('P30D'),
        };
    }

    /** Simultaneous devices. Signing in on one more revokes the least recently used. */
    public function maxDevices(): int
    {
        return match ($this) {
            self::Desktop => 3,
            self::Mobile => 2,
        };
    }
}
