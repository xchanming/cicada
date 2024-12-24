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
class Migration1552360944MediaFolderConfigurationNoAssoc extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552360944;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `media_folder_configuration`
                ADD COLUMN `no_association` BOOL NULL AFTER `private`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
