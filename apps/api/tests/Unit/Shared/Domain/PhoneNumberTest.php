<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain;

use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function writtenForms(): iterable
    {
        yield 'Spanish mobile without prefix' => ['600123456', '+34600123456'];
        yield 'with spaces, as office types it' => ['600 12 34 56', '+34600123456'];
        yield 'with dashes and dots' => ['600-123.456', '+34600123456'];
        yield 'with +34' => ['+34 600 123 456', '+34600123456'];
        yield 'with 0034' => ['0034600123456', '+34600123456'];
        yield 'Spanish 7xx mobile' => ['712345678', '+34712345678'];
        yield 'Portuguese number' => ['+351 912 345 678', '+351912345678'];
    }

    #[DataProvider('writtenForms')]
    public function testNormalisesToE164(string $written, string $expected): void
    {
        self::assertSame($expected, PhoneNumber::fromString($written)->value);
    }

    /** @return iterable<string, array{string}> */
    public static function notPhones(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['60012'];
        yield 'letters' => ['600abc456'];
        yield 'national number not starting 6-9' => ['500123456'];
        yield 'leading zero after +' => ['+0600123456'];
    }

    #[DataProvider('notPhones')]
    public function testRejectsWhatIsNotAPhone(string $written): void
    {
        try {
            PhoneNumber::fromString($written);
            self::fail('Expected ValidationFailed');
        } catch (ValidationFailed $exception) {
            self::assertSame([['field' => 'phone', 'code' => 'phone_invalid']], $exception->violations);
        }
    }

    public function testNationalDigitsDropTheSpanishPrefix(): void
    {
        self::assertSame('600123456', PhoneNumber::fromString('+34600123456')->nationalDigits());
        self::assertSame('351912345678', PhoneNumber::fromString('+351912345678')->nationalDigits());
    }
}
