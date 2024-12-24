<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1674200008UpdateOrderViewerRolePrivileges extends MigrationStep
{
    final public const NEW_PRIVILEGES = [
        'order.viewer' => [
            'media_default_folder:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1674200008;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::NEW_PRIVILEGES);
    }
}
