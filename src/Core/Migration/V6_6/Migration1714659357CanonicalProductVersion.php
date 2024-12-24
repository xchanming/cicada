<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * This migration repairs the FK of canonical_product_id to include the version_id. To fix MySQL 8.4 compatibility
 *
 * @internal
 */
#[Package('core')]
class Migration1714659357CanonicalProductVersion extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1714659357;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'product', 'canonical_product_version_id', 'binary(16)', true, '0x0fa91ce3e96a4bc2be4bd9ce752c3425');

        /** @phpstan-ignore cicada.dropStatement (As the foreign key is directly added again, the drop is fine in this case) */
        $this->dropForeignKeyIfExists($connection, 'product', 'fk.product.canonical_product_id');
        $this->dropIndexIfExists($connection, 'product', 'fk.product.canonical_product_id');

        $connection->executeStatement('
            ALTER TABLE `product`
            ADD CONSTRAINT `fk.product.canonical_product_id`
            FOREIGN KEY (`canonical_product_id` , `canonical_product_version_id`)
            REFERENCES `product` (`id`, `version_id`)
            ON DELETE SET NULL
        ');
    }
}
