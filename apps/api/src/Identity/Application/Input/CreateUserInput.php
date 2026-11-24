<?php

declare(strict_types=1);

namespace App\Identity\Application\Input;

use App\Identity\Domain\Role;
use Symfony\Component\Validator\Constraints as Assert;

/** Alta de usuario desde oficina (formularios-05, sección 6.2). The phone is normalised by the use case. */
final readonly class CreateUserInput
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 150, maxMessage: 'too_long')]
        public string $fullName = '',
        #[Assert\NotBlank(message: 'required')]
        #[Assert\Length(max: 30, maxMessage: 'too_long')]
        public string $phone = '',
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
