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
class Migration1632215760MoveDataFromEventActionToFlow extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1632215760;
    }

    public function update(Connection $connection): void
    {
        $migrate = new Migration1625583619MoveDataFromEventActionToFlow();
        $migrate->internal = true;
        $migrate->update($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
