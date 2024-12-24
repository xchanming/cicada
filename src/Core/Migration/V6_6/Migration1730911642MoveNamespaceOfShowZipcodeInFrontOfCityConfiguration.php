<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1730911642MoveNamespaceOfShowZipcodeInFrontOfCityConfiguration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1730911642;
    }

    public function update(Connection $connection): void
    {
        $connection->update('system_config', [
            'configuration_key' => 'core.loginRegistration.showZipcodeInFrontOfCity',
        ], [
            'configuration_key' => 'core.address.showZipcodeInFrontOfCity',
        ]);
    }
}
