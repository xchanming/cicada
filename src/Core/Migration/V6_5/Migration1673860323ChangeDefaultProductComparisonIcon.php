<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1673860323ChangeDefaultProductComparisonIcon extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1673860323;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE `sales_channel_type` SET `icon_name` = "regular-rocket" WHERE `icon_name` = "default-object-rocket"');
    }
}
