<?php declare(strict_types=1);

namespace Cicada\Core\Migration\Traits;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
trait StateMachineMigrationTrait
{
    private function import(StateMachineMigration $migration, Connection $connection): StateMachineMigration
    {
        return (new StateMachineMigrationImporter($connection))->importStateMachine($migration);
    }
}
