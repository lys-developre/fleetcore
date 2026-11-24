<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestPasswordResetInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 30, maxMessage: 'too_long')]
        public string $phone = '',
    ) {
    }
}
