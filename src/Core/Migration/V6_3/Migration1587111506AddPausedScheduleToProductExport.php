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
class Migration1587111506AddPausedScheduleToProductExport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1587111506;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE product_export ADD COLUMN paused_schedule TINYINT(1) NULL DEFAULT \'0\'');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
