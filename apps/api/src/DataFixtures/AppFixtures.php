<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\UserFactory;
use App\Identity\Domain\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Development data: one account per role, all with the same password, plus an operator still waiting
 * for activation. Realistic demo data arrives with H9.
 */
final class AppFixtures extends Fixture
{
    public const string PASSWORD = UserFactory::DEFAULT_PASSWORD;

    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne(['fullName' => 'Ana Administradora', 'phone' => '+34600000001', 'roles' => [Role::Admin], 'email' => 'admin@demo.fleetcore.test']);
        UserFactory::createOne(['fullName' => 'Olga Oficina', 'phone' => '+34600000002', 'roles' => [Role::Office]]);
        UserFactory::createOne(['fullName' => 'Óscar Operario', 'phone' => '+34600000003', 'roles' => [Role::Operator], 'operatorNumber' => 'OP-01']);
        UserFactory::createOne(['fullName' => 'Carmen Consulta', 'phone' => '+34600000004', 'roles' => [Role::Viewer]]);
        UserFactory::createOne(['fullName' => 'Pedro Pendiente', 'phone' => '+34600000005', 'roles' => [Role::Operator], 'operatorNumber' => 'OP-02', 'password' => null]);
    }
}
