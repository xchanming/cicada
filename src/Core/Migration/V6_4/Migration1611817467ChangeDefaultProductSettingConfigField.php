<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1611817467ChangeDefaultProductSettingConfigField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1611817467;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_search_config SET and_logic = 1');

        $connection->executeStatement('UPDATE product_search_config_field SET searchable = 1, tokenize = 1 WHERE field = :fieldName', [
            'fieldName' => 'name',
        ]);

        $connection->executeStatement('UPDATE product_search_config_field SET searchable = 1 WHERE field IN (:fieldsName)', [
            'fieldsName' => ['productNumber', 'ean', 'customSearchKeywords', 'manufacturer.name', 'manufacturerNumber'],
        ], ['fieldsName' => ArrayParameterType::STRING,
        ]);

        $connection->executeStatement('UPDATE product_search_config_field SET field = :newName where field = :oldName', [
            'newName' => 'options.name',
            'oldName' => 'variantRestrictions',
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
