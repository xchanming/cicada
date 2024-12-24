<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1689776940AddCartSourceField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1689776940;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'order',
            column: 'source',
            type: 'VARCHAR(255)'
        );
    }
}
