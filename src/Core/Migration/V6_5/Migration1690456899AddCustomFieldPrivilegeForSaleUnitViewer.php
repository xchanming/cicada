<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1690456899AddCustomFieldPrivilegeForSaleUnitViewer extends MigrationStep
{
    final public const NEW_PRIVILEGES = [
        'scale_unit.viewer' => [
            'custom_field_set:read',
            'custom_field:read',
            'custom_field_set_relation:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1690456899;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalPrivileges($connection, self::NEW_PRIVILEGES);
    }
}
