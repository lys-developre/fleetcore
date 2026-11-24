<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\PasswordPolicy;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Shared\Domain\Exception\ValidationFailed;
use App\Shared\Domain\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** formularios-05, sección 6.3. */
final class PasswordPolicyTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function rejected(): iterable
    {
        yield 'nine characters' => ['retro-123', 'password_too_short'];
        yield 'first name' => ['josemaria-cazo', 'password_contains_personal_data'];
        yield 'surname, any case and accents' => ['XXMÁRQUEZxx99', 'password_contains_personal_data'];
        yield 'the phone number' => ['mi-movil-600123456', 'password_contains_personal_data'];
        yield 'the phone with separators' => ['600 12 34 56 obra', 'password_contains_personal_data'];
    }

    #[DataProvider('rejected')]
    public function testRejects(string $password, string $code): void
    {
        try {
            $this->policy()->check($password, self::user());
            self::fail('Expected ValidationFailed');
        } catch (ValidationFailed $exception) {
            self::assertSame([['field' => 'password', 'code' => $code]], $exception->violations);
        }
    }

    public function testAcceptsLongPasswordsWithoutSymbolsOrCapitals(): void
    {
        $this->policy()->check('la retro sube al camion', self::user());
        $this->addToAssertionCount(1);
    }

    public function testShortNameParticlesDoNotCount(): void
    {
        $user = new User('Ana de la Fuente', PhoneNumber::fromString('600123456'), [Role::Operator]);

        $this->policy()->check('delantera-de-la-obra', $user);
        $this->addToAssertionCount(1);
    }

    public function testRejectsKnownBreachedPasswords(): void
    {
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('compromised', null, [], null, '', 'qwertyuiop'),
        ]));

        $this->expectException(ValidationFailed::class);
        new PasswordPolicy($validator)->check('qwertyuiop', self::user());
    }

    private function policy(): PasswordPolicy
    {
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return new PasswordPolicy($validator);
    }

    private static function user(): User
    {
        return new User('José María Márquez', PhoneNumber::fromString('600123456'), [Role::Operator]);
    }
}
