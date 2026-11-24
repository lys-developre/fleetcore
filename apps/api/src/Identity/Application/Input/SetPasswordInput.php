<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use App\Identity\Domain\SessionProfile;
use Symfony\Component\Validator\Constraints as Assert;

/** Choosing a password from an activation or recovery link. The policy itself is App\Identity\Application\PasswordPolicy. */
final readonly class SetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'required')]
        public string $password = '',
        #[Assert\Choice(choices: ['desktop', 'mobile'], message: 'profile_invalid')]
        public string $profile = 'desktop',
    ) {
    }

    public function sessionProfile(): SessionProfile
    {
        return SessionProfile::from($this->profile);
    }
}
