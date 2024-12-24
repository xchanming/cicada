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
class Migration1631703921MigrateLineItemsInCartRule extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1631703921;
    }

    public function update(Connection $connection): void
    {
        // moved to V6_5/Migration1669291632MigrateLineItemsInCartRule.php
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
