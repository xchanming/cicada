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
class Migration1650981517RemoveCicadaId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1650981517;
    }

    public function update(Connection $connection): void
    {
        // nth
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `system_config` WHERE `configuration_key` = "core.store.cicadaId"'
        );
    }
}
