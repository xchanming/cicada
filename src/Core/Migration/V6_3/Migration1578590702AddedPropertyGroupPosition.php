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
class Migration1578590702AddedPropertyGroupPosition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578590702;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `property_group_translation` ADD `position` INT(11) NULL DEFAULT 1 AFTER `description`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
