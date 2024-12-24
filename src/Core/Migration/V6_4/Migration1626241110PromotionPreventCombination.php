<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1626241110PromotionPreventCombination extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1626241110;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion` ADD COLUMN `prevent_combination` TINYINT(1) NOT NULL DEFAULT 0 AFTER `customer_restriction`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
