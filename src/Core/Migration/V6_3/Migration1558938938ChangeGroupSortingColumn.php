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
class Migration1558938938ChangeGroupSortingColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1558938938;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product` ADD `configurator_group_config` json NULL AFTER `configurator_group_sorting`;');
        $connection->executeStatement('ALTER TABLE `product` DROP COLUMN `configurator_group_sorting`;');
        $connection->executeStatement('ALTER TABLE `product` ADD COLUMN `display_in_listing` TINYINT(1) DEFAULT 1');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
