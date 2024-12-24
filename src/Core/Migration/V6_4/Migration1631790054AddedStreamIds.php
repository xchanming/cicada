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
class Migration1631790054AddedStreamIds extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1631790054;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product` ADD `stream_ids` json NULL AFTER `category_ids`');
        $this->registerIndexer($connection, 'product.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
