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
class Migration1595489705PreventOldListingPrices extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1595489705;
    }

    public function update(Connection $connection): void
    {
        $this->registerIndexer($connection, 'product.indexer');

        $sql = 'CREATE TRIGGER product_listing_price_update BEFORE UPDATE ON product
            FOR EACH ROW
            BEGIN
                IF @TRIGGER_DISABLED IS NULL OR @TRIGGER_DISABLED = 0 THEN
                    IF NEW.listing_prices IS NOT NULL THEN
                        IF JSON_CONTAINS_PATH(NEW.listing_prices, \'one\', \'$.structs\') = 1 THEN
                            SET NEW.listing_prices = NULL;
                        END IF;
                    END IF;
                END IF;
            END;';

        $this->createTrigger($connection, $sql);

        $connection->executeStatement('UPDATE product SET listing_prices = NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
