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
class Migration1665064823AddRuleAreas extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1665064823;
    }

    public function update(Connection $connection): void
    {
        if (!$this->columnExists($connection, 'rule', 'areas')) {
            $connection->executeStatement('ALTER TABLE `rule` ADD `areas` json NULL AFTER `invalid`;');

            $this->registerIndexer($connection, 'rule.indexer');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
