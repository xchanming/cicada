<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1656397126AddMainVariantConfiguration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1656397126;
    }

    public function update(Connection $connection): void
    {
        if (!EntityDefinitionQueryHelper::columnExists($connection, 'product', 'display_parent')) {
            $connection->executeStatement(
                'ALTER TABLE `product` ADD COLUMN `display_parent` TINYINT(1) NULL DEFAULT NULL'
            );
        }

        if (!EntityDefinitionQueryHelper::columnExists($connection, 'product', 'variant_listing_config')) {
            // Will be dropped anyway in future migrations: Cicada\Core\Migration\V6_5\Migration1678969082DropVariantListingFields
            $this->dropForeignKeyIfExists($connection, 'product', 'fk.product.main_variant_id');

            $connection->executeStatement(
                'ALTER TABLE `product` ADD COLUMN `variant_listing_config` JSON
                        GENERATED ALWAYS AS (CASE WHEN `display_parent` IS NOT NULL OR `main_variant_id` IS NOT NULL OR `configurator_group_config` IS NOT NULL
                            THEN (JSON_OBJECT( \'displayParent\', `display_parent`, \'mainVariantId\', LOWER(HEX(`main_variant_id`)) ,\'configuratorGroupConfig\', JSON_EXTRACT(`configurator_group_config`, \'$\')))
                        END) VIRTUAL'
            );
        }
    }
}
