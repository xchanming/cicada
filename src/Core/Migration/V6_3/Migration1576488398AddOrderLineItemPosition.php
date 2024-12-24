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
class Migration1576488398AddOrderLineItemPosition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1576488398;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order_line_item`
            ADD COLUMN `position` INT(11) NOT NULL DEFAULT 1 AFTER `good`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
