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
class Migration1629204538AddTimeZoneField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1629204538;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `user` ADD `time_zone` varchar(255) NOT NULL DEFAULT \'Asia/Shanghai\' AFTER `last_updated_password_at`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
