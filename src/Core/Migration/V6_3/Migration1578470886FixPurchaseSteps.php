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
class Migration1578470886FixPurchaseSteps extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578470886;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product SET purchase_steps = 1 WHERE purchase_steps < 1');
        $connection->executeStatement('UPDATE product SET min_purchase = 1 WHERE min_purchase < 1');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
