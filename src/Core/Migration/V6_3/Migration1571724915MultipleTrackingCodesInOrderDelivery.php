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
class Migration1571724915MultipleTrackingCodesInOrderDelivery extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571724915;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order_delivery`
            ADD COLUMN `tracking_codes` JSON NULL AFTER `shipping_method_id`,
            ADD CONSTRAINT `json.order_delivery.tracking_codes` CHECK (JSON_VALID(`tracking_codes`));
        ');

        $connection->executeStatement('
            UPDATE `order_delivery`
            SET `tracking_codes` = IF(`tracking_code` IS NULL OR `tracking_code` = "", JSON_ARRAY(), JSON_ARRAY(`tracking_code`));
        ');

        $connection->executeStatement('
            ALTER TABLE `order_delivery`
            MODIFY COLUMN `tracking_codes` JSON NOT NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order_delivery`
            DROP COLUMN `tracking_code`;
        ');
    }
}
