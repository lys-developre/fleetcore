<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\User;
use App\Shared\Domain\Exception\ValidationFailed;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * formularios-05, sección 6.3: length over complexity. At least 10 characters, not in known breaches,
 * and nothing from the user's own name or phone. No forced expiry.
 */
final readonly class PasswordPolicy
{
    public const int MIN_LENGTH = 10;
    /** The password hasher refuses longer input; nobody types this much on a phone. */
    public const int MAX_LENGTH = 4096;
    private const int MIN_NAME_PART_LENGTH = 3;

    public function __construct(private ValidatorInterface $validator)
    {
    }

    /** @throws ValidationFailed on the "password" field */
    public function check(string $password, User $user): void
    {
        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH) {
            throw ValidationFailed::forField('password', 'password_too_short');
        }
        if ($length > self::MAX_LENGTH) {
            throw ValidationFailed::forField('password', 'too_long');
        }
        if ($this->containsPersonalData($password, $user)) {
            throw ValidationFailed::forField('password', 'password_contains_personal_data');
        }
        // Have I Been Pwned range API (k-anonymity: only 5 hash characters leave the server). Disabled in tests.
        if (\count($this->validator->validate($password, new NotCompromisedPassword(skipOnError: true))) > 0) {
            throw ValidationFailed::forField('password', 'password_compromised');
        }
    }

    private function containsPersonalData(string $password, User $user): bool
    {
        $haystack = self::fold($password);

        foreach (preg_split('/\s+/u', $user->getFullName()) ?: [] as $part) {
            $part = self::fold($part);
            if (mb_strlen($part) >= self::MIN_NAME_PART_LENGTH && str_contains($haystack, $part)) {
                return true;
            }
        }

        $digits = (string) preg_replace('/\D/', '', $password);

        return str_contains($digits, $user->getMobilePhone()->nationalDigits());
    }

    /** "José-Mª" and "jose" compare equal: lower case, no accents, letters and digits only. */
    private static function fold(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', new UnicodeString($value)->ascii()->lower()->toString());
    }
}
