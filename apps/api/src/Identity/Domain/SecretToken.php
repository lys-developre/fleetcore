<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/**
 * Random secrets handed to a device or put in an SMS link. Only their SHA-256 is stored: a database leak
 * does not give away sessions or links. SHA-256 without salt is enough for 256-bit random values.
 */
final class SecretToken
{
    /** @return array{plain: string, hash: string} */
    public static function generate(): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return ['plain' => $plain, 'hash' => self::hash($plain)];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
