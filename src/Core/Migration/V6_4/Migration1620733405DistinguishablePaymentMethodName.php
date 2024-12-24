<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1620733405DistinguishablePaymentMethodName extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620733405;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `payment_method_translation`
            ADD COLUMN `distinguishable_name` VARCHAR(255) NULL AFTER `name`
        ');

        $this->registerIndexer($connection, 'payment_method.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
