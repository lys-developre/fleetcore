<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use App\Factory\UserFactory;
use App\Identity\Domain\Role;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * The database refuses to end a transaction without an active administrator (formularios-05, sección 10),
 * even if the application check is bypassed. The trigger is deferred to commit; DAMA never commits, so each
 * test forces the check with SET CONSTRAINTS ALL IMMEDIATE.
 */
final class ActiveAdminTriggerTest extends KernelTestCase
{
    use Factories;

    public function testRefusesToDeactivateTheLastActiveAdministrator(): void
    {
        $admin = UserFactory::new()->admin()->create();
        UserFactory::new()->operator()->create();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('at least one active administrator');

        $this->connection()->executeStatement("UPDATE app_user SET status = 'deactivated', status_reason = 'x' WHERE id = ?", [$admin->getId()->toRfc4122()]);
        $this->connection()->executeStatement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    public function testRefusesToRemoveTheRoleFromTheLastActiveAdministrator(): void
    {
        $admin = UserFactory::new()->with(['roles' => [Role::Admin, Role::Operator]])->create();

        $this->expectException(DriverException::class);

        $this->connection()->executeStatement("DELETE FROM app_user_role WHERE user_id = ? AND role = 'ROLE_ADMIN'", [$admin->getId()->toRfc4122()]);
        $this->connection()->executeStatement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    public function testAllowsHandingOverToAnotherAdministratorInTheSameTransaction(): void
    {
        $outgoing = UserFactory::new()->admin()->create();
        $incoming = UserFactory::new()->operator()->create();

        $this->connection()->executeStatement("UPDATE app_user SET status = 'deactivated', status_reason = 'Jubilación' WHERE id = ?", [$outgoing->getId()->toRfc4122()]);
        $this->connection()->executeStatement("INSERT INTO app_user_role (user_id, role) VALUES (?, 'ROLE_ADMIN')", [$incoming->getId()->toRfc4122()]);
        $this->connection()->executeStatement('SET CONSTRAINTS ALL IMMEDIATE');

        self::assertSame(1, (int) $this->connection()->fetchOne("SELECT count(*) FROM app_user_role r JOIN app_user u ON u.id = r.user_id WHERE r.role = 'ROLE_ADMIN' AND u.status = 'active'"));
    }

    public function testRefusesAnActiveUserWithoutPassword(): void
    {
        $pending = UserFactory::new()->pending()->create();
        UserFactory::new()->admin()->create();

        $this->expectException(DriverException::class);

        $this->connection()->executeStatement("UPDATE app_user SET status = 'active' WHERE id = ?", [$pending->getId()->toRfc4122()]);
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }
}
