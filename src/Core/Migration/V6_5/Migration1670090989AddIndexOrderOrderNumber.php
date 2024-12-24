<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 */
#[Package('core')]
class Migration1670090989AddIndexOrderOrderNumber extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1670090989;
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function update(Connection $connection): void
    {
        $key = $connection->executeQuery(
            'SHOW KEYS FROM `order` WHERE Column_name="order_number" AND Key_name="idx.order_number"'
        )->fetchAssociative();

        if (!empty($key)) {
            return;
        }

        $connection->executeStatement(
            'ALTER TABLE `order` ADD INDEX `idx.order_number` (`order_number`)'
        );
    }
}
