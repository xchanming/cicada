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
class Migration1643366069AddSeoUrlUpdaterIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1643366069;
    }

    public function update(Connection $connection): void
    {
        if ($this->hasIndexAlready($connection)) {
            return;
        }

        $connection->executeStatement('CREATE INDEX `idx.delete_query` ON seo_url (foreign_key, sales_channel_id);');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function hasIndexAlready(Connection $connection): bool
    {
        $indices = $connection->fetchAllAssociative('SHOW INDEX FROM seo_url');

        $grouped = [];

        foreach ($indices as $index) {
            $grouped[$index['Key_name']][] = $index['Column_name'];
        }

        foreach ($grouped as $columns) {
            if (\count($columns) === 2 && \in_array('foreign_key', $columns, true) && \in_array('sales_channel_id', $columns, true)) {
                return true;
            }
        }

        return false;
    }
}
