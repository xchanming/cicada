<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('buyers-experience')]
class Migration1662533751AddCustomEntityTypeIdToCategory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1662533751;
    }

    public function update(Connection $connection): void
    {
        $added = $this->addColumn(
            connection: $connection,
            table: 'category',
            column: 'custom_entity_type_id',
            type: 'BINARY(16)',
        );

        if ($added) {
            $connection->executeStatement(
                'ALTER TABLE `category`
                    ADD CONSTRAINT `fk.category.custom_entity_type_id` FOREIGN KEY (`custom_entity_type_id`)
                    REFERENCES `custom_entity` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;'
            );
        }
    }
}
