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
class Migration1619703075ScheduleIndexers extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1619703075;
    }

    public function update(Connection $connection): void
    {
        $this->registerIndexer($connection, 'product.indexer');
        $this->registerIndexer($connection, 'product_stream.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
