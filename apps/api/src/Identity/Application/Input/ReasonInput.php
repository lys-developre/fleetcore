<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Reason for blocking (optional) or deactivating (required, checked by the entity). */
final readonly class ReasonInput
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'too_long')]
        public ?string $reason = null,
    ) {
    }
}
