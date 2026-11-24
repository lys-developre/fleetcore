<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use App\Identity\Domain\Role;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Editing someone else's data. Changing the phone is not offered yet: it requires confirming the new number
 * by SMS and closing every session (formularios-05, regla 3.3.3).
 */
final readonly class UpdateUserInput
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 150, maxMessage: 'too_long')]
        public string $fullName = '',
        #[Assert\Email(message: 'email_invalid')]
        #[Assert\Length(max: 254, maxMessage: 'too_long')]
        public ?string $email = null,
        #[Assert\Length(max: 20, maxMessage: 'too_long')]
        public ?string $operatorNumber = null,
        #[Assert\Count(min: 1, minMessage: 'roles_required')]
        #[Assert\All([new Assert\Choice(callback: [Role::class, 'values'], message: 'role_invalid')])]
        public array $roles = [],
    ) {
    }

    /** @return list<Role> */
    public function roleList(): array
    {
        return Role::fromValues($this->roles);
    }
}
