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
#[Package('framework')]
class Migration1575036586FixProductConfiguratorSettingsConstraint extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1575036586;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product_configurator_setting` DROP FOREIGN KEY `fk.product_configurator_setting.property_group_option_id`
        ');

        $connection->executeStatement('
            ALTER TABLE `product_configurator_setting`
            ADD CONSTRAINT `fk.product_configurator_setting.property_group_option_id`
            FOREIGN KEY (`property_group_option_id`)
            REFERENCES `property_group_option` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
