<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1704703562ScheduleMediaPathIndexer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1704703562;
    }

    public function update(Connection $connection): void
    {
        // schedule indexer again to fix media path and reindex the denormalized thumbnails
        $this->registerIndexer($connection, 'media.path.post_update');
    }
}
