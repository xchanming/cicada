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
class Migration1627650101AddUploadPluginRolePrivilege extends MigrationStep
{
    final public const NEW_PRIVILEGES = [
        'system.plugin_maintain' => [
            'user_config:read',
            'user_config:create',
            'user_config:update',
            'system.plugin_upload',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1627650101;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::NEW_PRIVILEGES);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
