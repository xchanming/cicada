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
class Migration1622010069AddCartRules extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1622010069;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `cart` ADD `rule_ids` json NOT NULL AFTER `sales_channel_id`;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
