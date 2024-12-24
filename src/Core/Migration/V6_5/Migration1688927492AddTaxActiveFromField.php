<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1688927492AddTaxActiveFromField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688927492;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'tax_rule',
            column: 'active_from',
            type: 'DATETIME(3)'
        );
    }
}
