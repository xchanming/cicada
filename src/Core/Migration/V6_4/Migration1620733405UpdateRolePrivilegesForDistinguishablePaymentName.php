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
class Migration1620733405UpdateRolePrivilegesForDistinguishablePaymentName extends MigrationStep
{
    private const NEW_PRIVILEGES = [
        'payment.viewer' => [
            'app:read',
            'app_payment_method:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1620733405;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::NEW_PRIVILEGES);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
