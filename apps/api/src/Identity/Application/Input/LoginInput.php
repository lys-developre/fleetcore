<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use App\Identity\Domain\SessionProfile;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 30, maxMessage: 'too_long')]
        public string $phone = '',
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 4096, maxMessage: 'too_long')]
        public string $password = '',
        /** Decided by the interface: the web app sends "mobile" from phones and tablets. */
        #[Assert\Choice(choices: ['desktop', 'mobile'], message: 'profile_invalid')]
        public string $profile = 'desktop',
    ) {
    }

    public function sessionProfile(): SessionProfile
    {
        return SessionProfile::from($this->profile);
    }
}
