<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1702982372FixProductCrossSellingSortByPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1702982372;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_cross_selling SET sort_by = "cheapestPrice" WHERE sort_by = "price"');
    }
}
