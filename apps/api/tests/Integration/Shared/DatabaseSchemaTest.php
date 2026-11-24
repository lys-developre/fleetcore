<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * The schema built by the migrations must match the Doctrine mappings exactly.
 * Catches an entity changed without its migration, or a migration edited by hand.
 *
 * Commands run through the full console application (not CommandTester) so console events fire:
 * the migrations bundle relies on them to hide its own doctrine_migration_versions table.
 */
final class DatabaseSchemaTest extends KernelTestCase
{
    public function testMappingsAreValidAndInSyncWithTheMigratedDatabase(): void
    {
        $tester = $this->console();

        $tester->run(['command' => 'doctrine:schema:validate']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
    }

    public function testEveryMigrationHasBeenApplied(): void
    {
        $tester = $this->console();

        $tester->run(['command' => 'doctrine:migrations:up-to-date']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
    }

    private function console(): ApplicationTester
    {
        $application = new Application(self::bootKernel());
        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }
}
