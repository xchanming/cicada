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
class Migration1638365464CheapestPricePercentageDynamicProductGroups extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1638365464;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_stream_filter SET field = "cheapestPrice.percentage" WHERE field = "price.percentage"');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
