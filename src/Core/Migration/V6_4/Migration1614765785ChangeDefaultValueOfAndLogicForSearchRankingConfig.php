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
class Migration1614765785ChangeDefaultValueOfAndLogicForSearchRankingConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614765785;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_search_config SET and_logic = 0');
        // implement update
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
