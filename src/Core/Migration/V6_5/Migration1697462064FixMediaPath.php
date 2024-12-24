<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1697462064FixMediaPath extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1697462064;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery('UPDATE media SET path = NULL WHERE file_name IS NULL OR file_name = \'\'');
        $connection->executeQuery('UPDATE media_thumbnail, media SET media_thumbnail.path = NULL WHERE (media.file_name IS NULL OR file_name = \'\') AND media.id = media_thumbnail.media_id');

        $this->registerIndexer($connection, 'media.path.post_update');
    }
}
