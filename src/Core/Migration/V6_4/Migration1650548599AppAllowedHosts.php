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
class Migration1650548599AppAllowedHosts extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1650548599;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM app'), 'Field');

        if (\in_array('allowed_hosts', $columns, true)) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `app` ADD COLUMN `allowed_hosts` JSON NULL AFTER `cookies`, ADD CONSTRAINT `json.app.allowed_hosts` CHECK (JSON_VALID(`allowed_hosts`))');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
