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
class Migration1610337444AddSlotConfigToProductTranslationTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610337444;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `product_translation`
    ADD COLUMN `slot_config` JSON AFTER `custom_fields`,
    ADD CONSTRAINT `json.product_translation.slot_config` CHECK (JSON_VALID(`slot_config`))
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
