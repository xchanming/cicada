<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1580202210DefaultRule extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580202210;
    }

    public function update(Connection $connection): void
    {
        $idRule = Uuid::randomBytes();
        $idCondition = Uuid::randomBytes();

        $connection->insert('rule', ['id' => $idRule, 'name' => 'Always valid (Default)', 'description' => null, 'priority' => 100, 'invalid' => 0, 'module_types' => null, 'custom_fields' => null, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT), 'updated_at' => null]);
        $connection->insert('rule_condition', ['id' => $idCondition, 'type' => 'alwaysValid', 'rule_id' => $idRule, 'parent_id' => null, 'value' => '{"isAlwaysValid": true}', 'position' => 0, 'custom_fields' => null, 'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT), 'updated_at' => null]);

        $this->registerIndexer($connection, 'Swag.RulePayloadIndexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
