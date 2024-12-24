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
class Migration1646397836UpdateRolePrivilegesOfOrderCreator extends MigrationStep
{
    final public const NEW_PRIVILEGES = [
        'order.creator' => [
            'api_proxy_switch-customer',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1646397836;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::NEW_PRIVILEGES);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
