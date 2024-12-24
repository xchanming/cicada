<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_7;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1720610755RemoveDefaultPaymentMethodFromCustomer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720610755;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'customer', 'default_payment_method_id')) {
            $connection->executeStatement('ALTER TABLE `customer` MODIFY COLUMN `default_payment_method_id` BINARY(16) NULL');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        if ($this->columnExists($connection, 'customer', 'default_payment_method_id')) {
            $this->dropForeignKeyIfExists($connection, 'customer', 'fk.customer.default_payment_method_id');
            $this->dropColumnIfExists($connection, 'customer', 'default_payment_method_id');
        }
    }
}
