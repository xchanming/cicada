<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1595160327AddPositionToCustomFieldSet extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1595160327;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `custom_field_set`
            ADD COLUMN `position` INT(11) NOT NULL DEFAULT 1 AFTER `active`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
