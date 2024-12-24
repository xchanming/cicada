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
class Migration1647511158AddRefundUrlToAppPaymentMethod extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1647511158;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `app_payment_method`'), 'Field');

        // Column already exists?
        if (!\in_array('refund_url', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `app_payment_method` ADD COLUMN `refund_url` VARCHAR(255) NULL AFTER `capture_url`');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
