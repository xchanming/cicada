<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('buyers-experience')]
class Migration1666689977AddPluginIdToCustomEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666689977;
    }

    public function update(Connection $connection): void
    {
        if (!$this->columnExists($connection, 'custom_entity', 'plugin_id')) {
            $connection->executeStatement('ALTER TABLE `custom_entity`
                ADD `plugin_id` BINARY(16) NULL,
                ADD CONSTRAINT `fk.custom_entity.plugin_id`
                    FOREIGN KEY (`plugin_id`)
                    REFERENCES `plugin` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE');
        }
    }
}
