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
#[Package('framework')]
class Migration1646125417AddPathFieldToMediaFolder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646125417;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `media_folder`'), 'Field');

        // only execute when the column does not exist
        if (!\in_array('path', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `media_folder` ADD `path` longtext NULL AFTER `child_count`;');
        }

        $this->registerIndexer($connection, 'media_folder.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
