<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1670854818RemoveEventActionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1670854818;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropTableIfExists($connection, 'event_action_sales_channel');
        $this->dropTableIfExists($connection, 'event_action_rule');
        $this->dropTableIfExists($connection, 'event_action');
    }
}
