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
class Migration1574063550AddCurrencyToProductExport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1574063550;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE product_export ADD COLUMN currency_id BINARY(16) NOT NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
