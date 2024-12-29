<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1720603803RemoveDefaultPaymentMethodRule extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720603803;
    }

    public function update(Connection $connection): void
    {
        // find all the deprecated rules
        $ruleIds = $connection->fetchFirstColumn('SELECT DISTINCT `rule_id` FROM `rule_condition` WHERE `type` = "customerDefaultPaymentMethod"');

        // migrate the rule condition types
        $connection->executeStatement('
            UPDATE `rule_condition`
            SET
                `type` = "paymentMethod",
                `value` = JSON_SET(
                           JSON_REMOVE(`value`, "$.methodIds"),
                           "$.paymentMethodIds",
                           IFNULL(JSON_EXTRACT(`value`, "$.methodIds"), JSON_ARRAY())
                          )
            WHERE `type` = "customerDefaultPaymentMethod"');

        // clear the payload in the updated rules
        $connection->executeStatement('UPDATE `rule` SET `payload` = NULL WHERE id in (:rule_ids)', [
            'rule_ids' => $ruleIds,
        ], [
            'rule_ids' => ArrayParameterType::BINARY,
        ]);

        // rebuild payload on rule (because it contains the conditions serialized)
        $this->registerIndexer($connection, 'rule.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
