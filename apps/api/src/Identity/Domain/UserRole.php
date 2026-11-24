<?php

declare(strict_types=1);

namespace App\Identity\Domain;

use Doctrine\ORM\Mapping as ORM;

/** One row per role held: app_user_role (user_id, role) as in modelo-datos-01. Managed only through User. */
#[ORM\Entity]
#[ORM\Table(name: 'app_user_role')]
class UserRole
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'roles')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Id]
        #[ORM\Column(length: 20, enumType: Role::class)]
        private Role $role,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
