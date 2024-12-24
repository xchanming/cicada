<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Migration;

use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;

#[Package('core')]
trait InheritanceUpdaterTrait
{
    protected function updateInheritance(Connection $connection, string $entity, string $propertyName): void
    {
        $sql = str_replace(
            ['#table#', '#column#'],
            [$entity, $propertyName],
            'ALTER TABLE `#table#` ADD COLUMN `#column#` binary(16) NULL'
        );

        $connection->executeStatement($sql);
    }
}
