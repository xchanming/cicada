<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1595578253CustomFieldSetSelection extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1595578253;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `product`
ADD `custom_field_set_selection_active` TINYINT(1) NULL;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
