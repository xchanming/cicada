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
class Migration1617953690AddCustomFieldToProductReview extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617953690;
    }

    public function update(Connection $connection): void
    {
        $featureColumn = $connection->fetchOne(
            'SHOW COLUMNS FROM `product_review` WHERE `Field` LIKE :column;',
            ['column' => 'custom_fields']
        );

        if ($featureColumn === false) {
            $connection->executeStatement(
                'ALTER TABLE `product_review`
                ADD COLUMN `custom_fields` JSON NULL AFTER `comment`,
                ADD CONSTRAINT `json.product_review.custom_fields` CHECK (JSON_VALID(`custom_fields`));'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
