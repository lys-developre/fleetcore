<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Presentation\Security;

use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Presentation\Security\UserVoter;
use App\Shared\Domain\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * One case per cell of formularios-05, matriz 5.4 (Usuarios y sistema), with the role hierarchy of security.yaml.
 * Target "operator" is a user holding only ROLE_OPERARIO; "office" holds ROLE_OFICINA; "self" is the actor.
 */
final class UserVoterTest extends TestCase
{
    /** @return iterable<string, array{string, Role, string, bool}> */
    public static function matrix(): iterable
    {
        $rows = [
            // attribute, target => [admin, office, operator, viewer]
            [UserVoter::LIST, 'none', [true, true, false, false]],
            [UserVoter::VIEW, 'operator', [true, true, false, false]],
            [UserVoter::VIEW, 'self', [true, true, true, true]],
            [UserVoter::ASSIGN_ROLES, 'roles:operator', [true, true, false, false]],
            [UserVoter::ASSIGN_ROLES, 'roles:office', [true, false, false, false]],
            [UserVoter::ASSIGN_ROLES, 'roles:admin', [true, false, false, false]],
            [UserVoter::ASSIGN_ROLES, 'roles:viewer', [true, false, false, false]],
            [UserVoter::ASSIGN_ROLES, 'roles:operator+office', [true, false, false, false]],
            [UserVoter::EDIT, 'operator', [true, true, false, false]],
            [UserVoter::EDIT, 'office', [true, false, false, false]],
            [UserVoter::RESEND_ACTIVATION, 'operator', [true, true, false, false]],
            [UserVoter::RESEND_ACTIVATION, 'office', [true, false, false, false]],
            [UserVoter::BLOCK, 'operator', [true, true, false, false]],
            [UserVoter::BLOCK, 'office', [true, false, false, false]],
            [UserVoter::BLOCK, 'operator+office', [true, false, false, false]],
            [UserVoter::DEACTIVATE, 'operator', [true, false, false, false]],
            [UserVoter::DELETE, 'operator', [true, false, false, false]],
            [UserVoter::REVOKE_SESSIONS, 'operator', [true, true, false, false]],
            [UserVoter::REVOKE_SESSIONS, 'office', [true, false, false, false]],
            [UserVoter::REVOKE_SESSIONS, 'self', [true, false, true, false]],
        ];

        $actors = ['admin' => Role::Admin, 'office' => Role::Office, 'operator' => Role::Operator, 'viewer' => Role::Viewer];
        foreach ($rows as [$attribute, $target, $expected]) {
            foreach (array_values($actors) as $i => $actor) {
                yield \sprintf('%s on %s by %s', $attribute, $target, array_keys($actors)[$i]) => [$attribute, $actor, $target, $expected[$i]];
            }
        }
    }

    #[DataProvider('matrix')]
    public function testMatrix(string $attribute, Role $actorRole, string $target, bool $granted): void
    {
        $actor = self::user($actorRole);
        $subject = match (true) {
            'none' === $target => null,
            'self' === $target => $actor,
            str_starts_with($target, 'roles:') => self::roles(substr($target, 6)),
            default => self::user(...self::roles($target)),
        };

        $vote = $this->voter()->vote(new UsernamePasswordToken($actor, 'api', $actor->getRoles()), $subject, [$attribute]);

        self::assertSame($granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testAbstainsOnOtherAttributes(): void
    {
        $actor = self::user(Role::Admin);

        $vote = $this->voter()->vote(new UsernamePasswordToken($actor, 'api', $actor->getRoles()), null, ['REPORT_VIEW']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    private function voter(): UserVoter
    {
        $hierarchy = new RoleHierarchy(['ROLE_ADMIN' => ['ROLE_OFICINA'], 'ROLE_OFICINA' => ['ROLE_CONSULTA']]);

        return new UserVoter(new AccessDecisionManager([new RoleHierarchyVoter($hierarchy)]));
    }

    /** @return list<Role> */
    private static function roles(string $spec): array
    {
        $map = ['admin' => Role::Admin, 'office' => Role::Office, 'operator' => Role::Operator, 'viewer' => Role::Viewer];

        return array_map(static fn (string $name): Role => $map[$name], explode('+', $spec));
    }

    private static function user(Role ...$roles): User
    {
        static $phone = 600000000;

        return new User('Usuario de Prueba', PhoneNumber::fromString((string) ++$phone), array_values($roles));
    }
}
