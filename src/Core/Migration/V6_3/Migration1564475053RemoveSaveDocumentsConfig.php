<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1564475053RemoveSaveDocumentsConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564475053;
    }

    public function update(Connection $connection): void
    {
        $connection->delete('system_config', [
            'configuration_key' => 'core.saveDocuments',
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
