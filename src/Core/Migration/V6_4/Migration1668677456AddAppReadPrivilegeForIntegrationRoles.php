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
class Migration1668677456AddAppReadPrivilegeForIntegrationRoles extends MigrationStep
{
    final public const NEW_PRIVILEGES = [
        'integration.viewer' => [
            'app:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1668677456;
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
