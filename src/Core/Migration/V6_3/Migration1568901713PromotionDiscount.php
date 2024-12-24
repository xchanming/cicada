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
class Migration1568901713PromotionDiscount extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1568901713;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion_discount` ADD `sorter_key` VARCHAR(255) DEFAULT NULL;');
        $connection->executeStatement('ALTER TABLE `promotion_discount` ADD `applier_key` VARCHAR(255) DEFAULT NULL;');
        $connection->executeStatement('ALTER TABLE `promotion_discount` ADD `usage_key` VARCHAR(255) DEFAULT NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
