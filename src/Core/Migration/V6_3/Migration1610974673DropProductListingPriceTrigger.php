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
class Migration1610974673DropProductListingPriceTrigger extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610974673;
    }

    public function update(Connection $connection): void
    {
        // implement update
    }

    public function updateDestructive(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                'DROP TRIGGER `product_listing_price_update`'
            );
        } catch (\Throwable) {
        }
    }
}
