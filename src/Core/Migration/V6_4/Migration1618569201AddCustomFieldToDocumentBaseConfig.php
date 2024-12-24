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
class Migration1618569201AddCustomFieldToDocumentBaseConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1618569201;
    }

    public function update(Connection $connection): void
    {
        $featureColumn = $connection->fetchOne(
            'SHOW COLUMNS FROM `document_base_config` WHERE `Field` LIKE :column;',
            ['column' => 'custom_fields']
        );

        if ($featureColumn === false) {
            $connection->executeStatement(
                'ALTER TABLE `document_base_config`
                ADD COLUMN `custom_fields` JSON NULL AFTER `config`,
                ADD CONSTRAINT `json.document_base_config.custom_fields` CHECK (JSON_VALID(`custom_fields`));'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
