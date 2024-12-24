<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1652166447AppLoadPriority extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1652166447;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM app'), 'Field');

        if (\in_array('template_load_priority', $columns, true)) {
            return;
        }

        $connection->executeStatement('ALTER TABLE app ADD template_load_priority INT DEFAULT "0"');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
