<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Factory\UserFactory;
use App\Identity\Application\ChangeUserStatus;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use App\Shared\Domain\AuditResult;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Tests\Support\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * formularios-05, matriz 5.4 "Usuarios y sistema" through HTTP: one case per row and role, plus the rules of
 * sección 3.3. The voter's logic is unit-tested cell by cell; here every route is proven to ask it.
 */
final class UserManagementTest extends ApiTestCase
{
    /**
     * @return iterable<string, array{string, array{string, string, array<string, mixed>|null}, array{int, int, int, int}}>
     */
    public static function matrix(): iterable
    {
        // [method, path template ({id} = an operator), body]  =>  expected status for [admin, office, operator, viewer]
        yield 'list users' => ['list', ['GET', '/api/users', null], [200, 200, 403, 403]];
        yield 'create an operator' => ['create-operator', ['POST', '/api/users', ['fullName' => 'Nuevo Operario', 'phone' => '+34677000001', 'roles' => ['ROLE_OPERARIO']]], [201, 201, 403, 403]];
        yield 'create office staff' => ['create-office', ['POST', '/api/users', ['fullName' => 'Nueva Oficina', 'phone' => '+34677000002', 'roles' => ['ROLE_OFICINA']]], [201, 403, 403, 403]];
        yield 'create an administrator' => ['create-admin', ['POST', '/api/users', ['fullName' => 'Nuevo Admin', 'phone' => '+34677000003', 'roles' => ['ROLE_ADMIN']]], [201, 403, 403, 403]];
        yield 'view an operator' => ['view', ['GET', '/api/users/{id}', null], [200, 200, 403, 403]];
        yield 'edit an operator' => ['edit', ['PATCH', '/api/users/{id}', ['fullName' => 'Nombre Corregido', 'roles' => ['ROLE_OPERARIO']]], [200, 200, 403, 403]];
        yield 'promote an operator to office' => ['promote', ['PATCH', '/api/users/{id}', ['fullName' => 'Ascendido', 'roles' => ['ROLE_OPERARIO', 'ROLE_OFICINA']]], [200, 403, 403, 403]];
        yield 'block an operator' => ['block', ['POST', '/api/users/{id}/block', ['reason' => 'Pruebas']], [200, 200, 403, 403]];
        yield 'deactivate an operator' => ['deactivate', ['POST', '/api/users/{id}/deactivate', ['reason' => 'Fin de contrato']], [200, 403, 403, 403]];
        yield 'close the sessions of an operator' => ['revoke', ['DELETE', '/api/users/{id}/sessions', null], [200, 200, 403, 403]];
    }

    /**
     * @param array{string, string, array<string, mixed>|null} $request
     * @param array{int, int, int, int}                        $expected
     */
    #[DataProvider('matrix')]
    public function testPermissionMatrix(string $case, array $request, array $expected): void
    {
        [$method, $path, $body] = $request;
        $roles = [Role::Admin, Role::Office, Role::Operator, Role::Viewer];

        foreach ($roles as $i => $role) {
            // A fresh target per actor: earlier actors may have blocked or renamed it
            $target = UserFactory::new()->operator()->create();
            $actor = UserFactory::new()->with(['roles' => [$role]])->create();
            $uri = str_replace('{id}', $target->getId()->toRfc4122(), $path);
            $payload = null === $body ? null : array_map(
                static fn (mixed $value): mixed => \is_string($value) && str_starts_with($value, '+346770') ? substr($value, 0, -1).$i : $value,
                $body,
            );

            $this->request($method, $uri, $payload, $this->tokenFor($actor));

            self::assertResponseStatusCodeSame($expected[$i], \sprintf('%s by %s: %s', $case, $role->value, $this->client->getResponse()->getContent()));
        }
    }

    public function testOfficeCannotManageOfficeStaffOrAdministrators(): void
    {
        $office = $this->tokenFor(UserFactory::new()->office()->create());
        $colleague = UserFactory::new()->office()->create();
        $admin = UserFactory::new()->admin()->create();

        foreach ([$colleague, $admin] as $target) {
            $this->request('PATCH', '/api/users/'.$target->getId(), ['fullName' => 'X', 'roles' => ['ROLE_OPERARIO']], $office);
            self::assertResponseStatusCodeSame(403);
            $this->request('POST', '/api/users/'.$target->getId().'/block', token: $office);
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function testEveryDenialIsAudited(): void
    {
        $operator = UserFactory::new()->operator()->create();

        $this->request('GET', '/api/users', token: $this->tokenFor($operator));
        self::assertResponseStatusCodeSame(403);

        $denials = $this->auditEntries('security.access_denied');
        self::assertCount(1, $denials);
        self::assertSame(AuditResult::Denied, $denials[0]->getResult());
        self::assertTrue($operator->getId()->equals($denials[0]->getActorId()));
        self::assertSame(['attributes' => ['USER_LIST'], 'route' => 'api_users_list', 'method' => 'GET'], $denials[0]->getDataAfter());
    }

    public function testPhoneEmailAndOperatorNumberAreUnique(): void
    {
        $admin = $this->tokenFor(UserFactory::new()->admin()->create());
        UserFactory::new()->create(['phone' => '+34600123456', 'email' => 'juan@obra.es', 'operatorNumber' => 'OP-01']);

        $json = $this->request('POST', '/api/users', [
            'fullName' => 'Otro Juan', 'phone' => '600 123 456', 'email' => 'JUAN@obra.es', 'operatorNumber' => 'OP-01', 'roles' => ['ROLE_OPERARIO'],
        ], $admin);

        self::assertResponseStatusCodeSame(422);
        self::assertViolation('phone', 'phone_taken', $json);
        self::assertViolation('email', 'email_taken', $json);
        self::assertViolation('operatorNumber', 'operator_number_taken', $json);
    }

    public function testValidatesTheNewUser(): void
    {
        $admin = $this->tokenFor(UserFactory::new()->admin()->create());

        $json = $this->request('POST', '/api/users', ['fullName' => '', 'phone' => '600', 'email' => 'no-es-correo', 'roles' => ['ROLE_ENCARGADO']], $admin);
        self::assertResponseStatusCodeSame(422);
        self::assertViolation('fullName', 'required', $json);
        self::assertViolation('email', 'email_invalid', $json);
        self::assertViolation('roles[0]', 'role_invalid', $json);

        self::assertViolation('phone', 'phone_invalid', $this->request('POST', '/api/users', ['fullName' => 'Juan', 'phone' => '600', 'roles' => ['ROLE_OPERARIO']], $admin));
        self::assertViolation('roles', 'roles_required', $this->request('POST', '/api/users', ['fullName' => 'Juan', 'phone' => '600123456', 'roles' => []], $admin));
    }

    public function testNobodyBlocksDeactivatesOrDeletesThemselves(): void
    {
        $admin = UserFactory::new()->admin()->create();
        UserFactory::new()->admin()->create();
        $token = $this->tokenFor($admin);

        foreach (['block', 'deactivate'] as $action) {
            self::assertErrorCode('cannot_target_self', $this->request('POST', '/api/users/'.$admin->getId().'/'.$action, ['reason' => 'x'], $token));
            self::assertResponseStatusCodeSame(409);
        }
        self::assertErrorCode('cannot_target_self', $this->request('DELETE', '/api/users/'.$admin->getId(), token: $token));
    }

    public function testAnAdministratorCannotDropTheirOwnAdminRole(): void
    {
        $admin = UserFactory::new()->admin()->create();
        UserFactory::new()->admin()->create();

        $json = $this->request('PATCH', '/api/users/'.$admin->getId(), ['fullName' => $admin->getFullName(), 'roles' => ['ROLE_OFICINA']], $this->tokenFor($admin));

        self::assertResponseStatusCodeSame(409);
        self::assertErrorCode('cannot_remove_own_admin', $json);
    }

    /**
     * Through HTTP the actor of a deactivation is itself an active administrator, so two always exist.
     * The use case still refuses on its own (and the database trigger behind it): checked without HTTP.
     */
    public function testTheApplicationRefusesToLeaveNoActiveAdministrator(): void
    {
        $onlyAdmin = UserFactory::new()->admin()->create();
        $office = UserFactory::new()->office()->create();

        try {
            self::getContainer()->get(ChangeUserStatus::class)->deactivate($onlyAdmin, 'Se jubila', $office);
            self::fail('Expected BusinessRuleViolation');
        } catch (BusinessRuleViolation $exception) {
            self::assertSame('last_active_admin', $exception->errorCode);
        }
    }

    public function testAnAdministratorMayDemoteAnotherOne(): void
    {
        $admin = UserFactory::new()->with(['roles' => [Role::Admin, Role::Operator]])->create();
        $otherAdmin = UserFactory::new()->admin()->create();
        $tokenOfOther = $this->tokenFor($otherAdmin);

        // The other admin demotes the first one: allowed, one active admin remains (the actor)
        $this->request('PATCH', '/api/users/'.$admin->getId(), ['fullName' => 'Antes Admin', 'roles' => ['ROLE_OPERARIO']], $tokenOfOther);
        self::assertResponseIsSuccessful();
        self::assertSame(['ROLE_OPERARIO'], $this->reload($admin)->getRoles());
    }

    public function testDeactivationNeedsAReasonAndClosesSessions(): void
    {
        $admin = $this->tokenFor(UserFactory::new()->admin()->create());
        $operator = UserFactory::new()->operator()->create();
        $operatorToken = $this->tokenFor($operator);

        self::assertViolation('reason', 'required', $this->request('POST', '/api/users/'.$operator->getId().'/deactivate', token: $admin));

        $json = $this->request('POST', '/api/users/'.$operator->getId().'/deactivate', ['reason' => 'Fin de contrato'], $admin);
        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertSame('deactivated', $json['status']);
        self::assertSame('Fin de contrato', $json['statusReason']);
        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $operatorToken));

        $this->request('POST', '/api/users/'.$operator->getId().'/readmit', token: $admin);
        self::assertResponseIsSuccessful();
        self::assertSame(UserStatus::Active, $this->reload($operator)->getStatus());
    }

    public function testUnblockAndUnlockAreSeparateActions(): void
    {
        $office = $this->tokenFor(UserFactory::new()->office()->create());
        $operator = UserFactory::new()->operator()->create(['phone' => '+34611223344']);
        for ($i = 0; $i < 5; ++$i) {
            $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => 'not-the-password']);
        }
        self::assertResponseStatusCodeSame(429);

        self::assertErrorCode('user_not_blocked', $this->request('POST', '/api/users/'.$operator->getId().'/unblock', token: $office));

        $json = $this->request('POST', '/api/users/'.$operator->getId().'/unlock', token: $office);
        self::assertResponseIsSuccessful();
        self::assertIsArray($json);
        self::assertNull($json['lockedUntil']);

        $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => UserFactory::DEFAULT_PASSWORD]);
        self::assertResponseIsSuccessful();
    }

    public function testOnlyUsersWithoutActivityCanBeDeleted(): void
    {
        $admin = $this->tokenFor(UserFactory::new()->admin()->create());
        $neverSignedIn = UserFactory::new()->pending()->create();
        $active = UserFactory::new()->operator()->create(['phone' => '+34611223344']);
        $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => UserFactory::DEFAULT_PASSWORD]);

        $this->request('DELETE', '/api/users/'.$neverSignedIn->getId(), token: $admin);
        self::assertResponseStatusCodeSame(204);
        self::assertNull($this->entityManager()->find(User::class, $neverSignedIn->getId()));

        self::assertErrorCode('user_has_activity', $this->request('DELETE', '/api/users/'.$active->getId(), token: $admin));
        self::assertResponseStatusCodeSame(409);
    }

    public function testUnknownUsersAre404(): void
    {
        $this->request('GET', '/api/users/01938a2e-0000-7000-8000-000000000000', token: $this->tokenFor(UserFactory::new()->admin()->create()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testChangesAreAuditedWithBeforeAndAfter(): void
    {
        $admin = $this->tokenFor(UserFactory::new()->admin()->create());
        $operator = UserFactory::new()->operator()->create(['fullName' => 'Juan Perez']);

        $this->request('PATCH', '/api/users/'.$operator->getId(), ['fullName' => 'Juan Pérez', 'roles' => ['ROLE_OPERARIO']], $admin);

        $entries = $this->auditEntries('user.updated');
        self::assertCount(1, $entries);
        self::assertSame('Juan Perez', $entries[0]->getDataBefore()['fullName'] ?? null);
        self::assertSame('Juan Pérez', $entries[0]->getDataAfter()['fullName'] ?? null);
    }
}
