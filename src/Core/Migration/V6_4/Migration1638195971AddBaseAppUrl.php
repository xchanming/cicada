<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1638195971AddBaseAppUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1638195971;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE `app` ADD `base_app_url` VARCHAR(1024) NULL AFTER `version`');
        } catch (\Exception) {
            // Column already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
