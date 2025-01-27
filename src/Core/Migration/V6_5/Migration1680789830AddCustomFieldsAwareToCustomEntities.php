<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1680789830AddCustomFieldsAwareToCustomEntities extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1680789830;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'custom_entity',
            column: 'custom_fields_aware',
            type: 'TINYINT(1)',
            nullable: false,
            default: '0'
        );

        $this->addColumn(
            connection: $connection,
            table: 'custom_entity',
            column: 'label_property',
            type: 'VARCHAR(255)'
        );
    }
}
