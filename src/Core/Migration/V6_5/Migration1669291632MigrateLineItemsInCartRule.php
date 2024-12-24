<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1669291632MigrateLineItemsInCartRule extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1669291632;
    }

    public function update(Connection $connection): void
    {
        // find all the deprecated rules
        $ruleConditions = $connection->fetchAllAssociative('SELECT DISTINCT rule_id FROM rule_condition WHERE type = "cartLineItemsInCart"');
        $ruleIds = array_map(fn ($condition) => $condition['rule_id'], $ruleConditions);

        // migrate the rule condition types
        $connection->executeStatement('UPDATE rule_condition SET type = "cartLineItem" WHERE type = "cartLineItemsInCart"');

        // clear the payload in the updated rules
        $connection->executeStatement('UPDATE rule SET payload = NULL WHERE id in (:rule_ids)', [
            'rule_ids' => $ruleIds,
        ], [
            'rule_ids' => ArrayParameterType::BINARY,
        ]);

        // rebuild payload on rule (because it contains the conditions serialized)
        $this->registerIndexer($connection, 'rule.indexer');
    }
}
