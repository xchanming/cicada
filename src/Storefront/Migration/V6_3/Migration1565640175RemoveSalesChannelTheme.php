<?php declare(strict_types=1);

namespace Cicada\Storefront\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1565640175RemoveSalesChannelTheme extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1565640175;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `sales_channel_theme`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
