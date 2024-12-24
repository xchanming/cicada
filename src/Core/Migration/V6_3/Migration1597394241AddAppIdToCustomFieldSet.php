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
class Migration1597394241AddAppIdToCustomFieldSet extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1597394241;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `custom_field_set`
            ADD COLUMN `app_id` BINARY(16) NULL AFTER `active`,
            ADD CONSTRAINT `fk.custom_field_set.app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // nth
    }
}
