<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1691662140MigrateAvailableStock extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1691662140;
    }

    public function update(Connection $connection): void
    {
        do {
            $ids = $connection->fetchFirstColumn(
                <<<'SQL'
                    SELECT id
                    FROM product
                    WHERE stock != available_stock
                    LIMIT 1000
                SQL,
            );

            $connection->executeStatement(
                'UPDATE product SET stock = available_stock WHERE id IN (:ids)',
                ['ids' => $ids],
                ['ids' => ArrayParameterType::BINARY]
            );
        } while (!empty($ids));
    }
}
