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
class Migration1600253602AddOrderRuleIds extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600253602;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `order` ADD COLUMN `rule_ids` JSON NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
