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
class Migration1591683158PromotionDiscount extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1591683158;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion_discount` ADD `picker_key` VARCHAR(255) DEFAULT NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
