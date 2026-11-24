<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Security;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Matriz de permisos 5.4 "Usuarios y sistema" of formularios-05. Administration manages everyone; office only
 * users who are operators and nothing else; a user acts on themselves only through /api/me.
 *
 * @extends Voter<string, User|list<Role>|null>
 */
final class UserVoter extends Voter
{
    public const string LIST = 'USER_LIST';
    public const string VIEW = 'USER_VIEW';
    /** Subject: the roles being given, on creation or edit. */
    public const string ASSIGN_ROLES = 'USER_ASSIGN_ROLES';
    public const string EDIT = 'USER_EDIT';
    public const string RESEND_ACTIVATION = 'USER_RESEND_ACTIVATION';
    public const string BLOCK = 'USER_BLOCK';
    public const string DEACTIVATE = 'USER_DEACTIVATE';
    public const string DELETE = 'USER_DELETE';
    public const string REVOKE_SESSIONS = 'USER_REVOKE_SESSIONS';

    private const array ATTRIBUTES = [
        self::LIST, self::VIEW, self::ASSIGN_ROLES, self::EDIT, self::RESEND_ACTIVATION,
        self::BLOCK, self::DEACTIVATE, self::DELETE, self::REVOKE_SESSIONS,
    ];

    public function __construct(private readonly AccessDecisionManagerInterface $decisions)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $actor = $token->getUser();
        if (!$actor instanceof User) {
            return false;
        }

        // Role hierarchy applies: ROLE_ADMIN includes ROLE_OFICINA
        $isAdmin = $this->decisions->decide($token, [Role::Admin->value]);
        $isOffice = $this->decisions->decide($token, [Role::Office->value]);

        $vote?->addReason(\sprintf('%s on %s', $attribute, get_debug_type($subject)));

        return match ($attribute) {
            self::LIST => $isOffice,
            self::VIEW => $isOffice || $subject === $actor,
            self::ASSIGN_ROLES => $isAdmin || ($isOffice && \is_array($subject) && [Role::Operator] === array_values($subject)),
            self::EDIT, self::RESEND_ACTIVATION, self::BLOCK => $isAdmin || ($isOffice && $subject instanceof User && $subject->isOnlyOperator()),
            self::DEACTIVATE, self::DELETE => $isAdmin,
            // Matrix row "Cerrar sesiones remotas": own devices only for operators (the phone left in another cab)
            self::REVOKE_SESSIONS => $isAdmin
                || ($subject === $actor && $actor->hasRole(Role::Operator))
                || ($isOffice && $subject instanceof User && $subject->isOnlyOperator()),
            default => false,
        };
    }
}
