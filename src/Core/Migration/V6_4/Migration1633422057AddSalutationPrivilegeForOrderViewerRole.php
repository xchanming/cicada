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
class Migration1633422057AddSalutationPrivilegeForOrderViewerRole extends MigrationStep
{
    private const PRIVILEGES = [
        'order.viewer' => [
            'salutation:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1633422057;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::PRIVILEGES);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
