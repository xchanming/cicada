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
class Migration1625816310AddDefaultToCartRuleIds extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1625816310;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE cart SET rule_ids = "[]" WHERE rule_ids = "" OR rule_ids IS NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
