<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1661759290AddDateAndCurrencyIndexToOrderTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1661759290;
    }

    public function update(Connection $connection): void
    {
        if ($this->indexExists($connection, 'order', 'idx.order_date_currency_id')) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `order` ADD INDEX `idx.order_date_currency_id` (`order_date`, `currency_id`)
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
