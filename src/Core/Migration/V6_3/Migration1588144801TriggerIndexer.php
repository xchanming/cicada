<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1588144801TriggerIndexer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1588144801;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('TRUNCATE product_keyword_dictionary');
        $this->registerIndexer($connection, 'product.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
