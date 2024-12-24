<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Database;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;

/**
 * @internal
 */
#[Package('core')]
class ReplicaConnection
{
    public static function ensurePrimary(): void
    {
        $connection = Kernel::getConnection();

        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToPrimary();
        }
    }
}
