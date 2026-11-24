<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** What a user may change about themselves (matriz 5.4: teléfono y correo; the phone arrives with its SMS confirmation). */
final readonly class UpdateOwnProfileInput
{
    public function __construct(
        #[Assert\Email(message: 'email_invalid')]
        #[Assert\Length(max: 254, maxMessage: 'too_long')]
        public ?string $email = null,
    ) {
    }
}
