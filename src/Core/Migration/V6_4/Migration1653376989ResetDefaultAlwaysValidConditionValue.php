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
class Migration1653376989ResetDefaultAlwaysValidConditionValue extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1653376989;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE `rule_condition` SET `value` = null WHERE `type` = \'alwaysValid\' AND `value` LIKE \'{"isAlwaysValid": true}\';');

        $this->registerIndexer($connection, 'Swag.RulePayloadIndexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
