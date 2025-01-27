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
#[Package('framework')]
class Migration1616496610CheapestPriceCustomProductGroups extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1616496610;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_stream_filter SET field = "cheapestPrice" WHERE field = "price"');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
