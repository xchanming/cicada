<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1698919811AddDeletedAtToCustomEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1698919811;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'custom_entity',
            column: 'deleted_at',
            type: 'DATETIME(3)'
        );
    }
}
