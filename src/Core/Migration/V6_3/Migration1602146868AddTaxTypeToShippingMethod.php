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
class Migration1602146868AddTaxTypeToShippingMethod extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1602146868;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `shipping_method`
            ADD `tax_type` varchar(50) NULL DEFAULT \'auto\' AFTER `delivery_time_id`,
            ADD `tax_id` BINARY(16) NULL AFTER `tax_type`,
            ADD CONSTRAINT `fk.shipping_method.tax_id` FOREIGN KEY (`tax_id`)
                REFERENCES `tax` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
