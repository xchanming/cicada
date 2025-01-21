<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1681382023AddCustomFieldAllowCartExpose extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1681382023;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'custom_field',
            column: 'allow_cart_expose',
            type: 'TINYINT(1)',
            nullable: false,
            default: '0',
        );

        $customFieldAllowList = $connection->fetchFirstColumn('SELECT JSON_UNQUOTE(JSON_EXTRACT(`value`, "$.renderedField.name")) as technical_name FROM rule_condition WHERE type = \'cartLineItemCustomField\';');

        foreach ($customFieldAllowList as $customField) {
            $connection->update(
                'custom_field',
                ['allow_cart_expose' => '1'],
                ['name' => $customField]
            );
        }
    }
}
