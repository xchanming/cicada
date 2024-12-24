<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1718658881AddValidationDataToOrderTransaction extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1718658881;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'order_transaction', 'validation_data', 'JSON NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
