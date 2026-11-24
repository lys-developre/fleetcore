<?php

declare(strict_types=1);

namespace App\Identity\Domain;

/**
 * Four fixed roles (formularios-05, sección 4). Hierarchy in security.yaml:
 * ROLE_ADMIN > ROLE_OFICINA > ROLE_CONSULTA; ROLE_OPERARIO stands alone. Codes stay in Spanish (glosario).
 */
enum Role: string
{
    case Admin = 'ROLE_ADMIN';
    case Office = 'ROLE_OFICINA';
    case Operator = 'ROLE_OPERARIO';
    case Viewer = 'ROLE_CONSULTA';

    /**
     * @param list<string> $values
     *
     * @return list<self>
     */
    public static function fromValues(array $values): array
    {
        $roles = [];
        foreach ($values as $value) {
            $role = self::from($value);
            if (!\in_array($role, $roles, true)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
