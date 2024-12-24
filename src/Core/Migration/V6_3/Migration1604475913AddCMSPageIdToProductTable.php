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
class Migration1604475913AddCMSPageIdToProductTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1604475913;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product`
            ADD COLUMN `cms_page_id` BINARY(16) NULL AFTER `product_media_version_id`,
            ADD CONSTRAINT `fk.product.cms_page_id` FOREIGN KEY (`cms_page_id`)
            REFERENCES `cms_page` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
