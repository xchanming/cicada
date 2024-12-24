<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\Media\DataAbstractionLayer\MediaFolderIndexer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1652864153ReindexMediaFolders extends MigrationStep
{
    /**
     * @codeCoverageIgnore
     */
    public function getCreationTimestamp(): int
    {
        return 1652864153;
    }

    public function update(Connection $connection): void
    {
        if ($this->isInstallation()) {
            return;
        }

        $this->registerIndexer($connection, 'media_folder.indexer', [MediaFolderIndexer::CHILD_COUNT_UPDATER]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
