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
class Migration1620215586FixManufacturerForeignKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620215586;
    }

    public function update(Connection $connection): void
    {
        $this->dropForeignKeyIfExists($connection, 'product', 'fk.product.product_manufacturer_id');
        $connection->executeStatement('ALTER TABLE `product` ADD FOREIGN KEY (`product_manufacturer_id`, `product_manufacturer_version_id`) REFERENCES `product_manufacturer` (`id`, `version_id`) ON DELETE SET NULL ON UPDATE CASCADE;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
