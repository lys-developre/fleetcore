<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Exception\ValidationFailed;

/**
 * Mobile phone in E.164 (`+34600123456`). It is the login identifier and the SMS channel, so it is stored
 * in exactly one shape: whatever the office or the operator typed is normalised here.
 */
final readonly class PhoneNumber implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    /**
     * Accepts spaces, dashes, dots and brackets, the `00` international prefix, and Spanish numbers without prefix.
     *
     * @throws ValidationFailed when the result is not a plausible E.164 number
     */
    public static function fromString(string $raw, string $field = 'phone'): self
    {
        $normalized = self::normalize($raw);
        if (null === $normalized) {
            throw ValidationFailed::forField($field, 'phone_invalid');
        }

        return new self($normalized);
    }

    public static function normalize(string $raw): ?string
    {
        $value = (string) preg_replace('/[\s\-.()]/', '', trim($raw));
        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        }
        // Spanish national numbering: 9 digits starting with 6, 7, 8 or 9
        if (1 === preg_match('/^[6-9]\d{8}$/', $value)) {
            $value = '+34'.$value;
        }

        return 1 === preg_match('/^\+[1-9]\d{7,14}$/', $value) ? $value : null;
    }

    /** National significant number, used to reject passwords that contain the phone. */
    public function nationalDigits(): string
    {
        return str_starts_with($this->value, '+34') ? substr($this->value, 3) : substr($this->value, 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
