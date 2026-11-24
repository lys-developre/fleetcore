<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Shared\Domain\AuditLog;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The audit trail can only grow (modelo-datos-01: "las tablas de firma y auditoría no se modifican ni borran"). */
final class AuditLogAppendOnlyTest extends KernelTestCase
{
    public function testRowsCannotBeUpdated(): void
    {
        $id = $this->insertEntry();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('append-only');

        $this->connection()->executeStatement("UPDATE audit_log SET action = 'tampered' WHERE id = ?", [$id]);
    }

    public function testRowsCannotBeDeleted(): void
    {
        $id = $this->insertEntry();

        $this->expectException(DriverException::class);

        $this->connection()->executeStatement('DELETE FROM audit_log WHERE id = ?', [$id]);
    }

    private function insertEntry(): string
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entry = new AuditLog('test.entry', new \DateTimeImmutable());
        $entityManager->persist($entry);
        $entityManager->flush();

        return $entry->getId()->toRfc4122();
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }
}
