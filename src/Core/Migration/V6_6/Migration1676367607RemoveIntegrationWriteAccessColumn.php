<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1676367607RemoveIntegrationWriteAccessColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1676367607;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'integration', 'write_access');
    }
}
