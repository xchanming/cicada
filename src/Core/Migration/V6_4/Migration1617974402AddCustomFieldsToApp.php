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
class Migration1617974402AddCustomFieldsToApp extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617974402;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `app_translation` ADD COLUMN `custom_fields` JSON NULL AFTER `privacy_policy_extensions`');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
