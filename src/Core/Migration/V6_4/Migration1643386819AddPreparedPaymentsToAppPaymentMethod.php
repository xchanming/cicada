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
#[Package('framework')]
class Migration1643386819AddPreparedPaymentsToAppPaymentMethod extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1643386819;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `app_payment_method`'), 'Field');

        // Column already exists?
        if (!\in_array('validate_url', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `app_payment_method` ADD COLUMN `validate_url` VARCHAR(255) NULL AFTER `finalize_url`');
        }

        if (!\in_array('capture_url', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `app_payment_method` ADD COLUMN `capture_url` VARCHAR(255) NULL AFTER `validate_url`');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
