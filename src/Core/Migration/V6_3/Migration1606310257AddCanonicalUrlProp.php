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
class Migration1606310257AddCanonicalUrlProp extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1606310257;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product`
            ADD `canonical_product_id` BINARY(16) NULL,
            ADD `canonical_product_version_id` BINARY(16) NULL,
            ADD `canonicalProduct` BINARY(16) NULL,
            ADD CONSTRAINT `fk.product.canonical_product_id`
                FOREIGN KEY (`canonical_product_id`, `canonical_product_version_id`)
                REFERENCES `product` (`id`, `version_id`)
                ON DELETE SET NULL

        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
