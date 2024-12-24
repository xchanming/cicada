<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1710493619ScheduleMediaPathIndexer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1710493619;
    }

    public function update(Connection $connection): void
    {
        // schedule indexer again to fix media path and reindex the denormalized thumbnails
        // before post updater where skipped in system update finish process
        $this->registerIndexer($connection, 'media.path.post_update');
    }
}
