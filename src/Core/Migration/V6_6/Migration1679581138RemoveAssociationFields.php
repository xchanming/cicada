<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1679581138RemoveAssociationFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1679581138;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'media_default_folder', 'association_fields')) {
            $connection->executeStatement('ALTER TABLE `media_default_folder` CHANGE `association_fields` `association_fields` JSON NULL');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'media_default_folder', 'association_fields');
    }
}
