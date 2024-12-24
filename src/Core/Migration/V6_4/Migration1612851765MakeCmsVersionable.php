<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MakeVersionableMigrationHelper;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1612851765MakeCmsVersionable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1612851765;
    }

    public function update(Connection $connection): void
    {
        $playbookGenerator = new MakeVersionableMigrationHelper($connection);

        $tables = [
            'cms_page',
            'cms_section',
            'cms_block',
        ];

        foreach ($tables as $table) {
            $hydratedData = $playbookGenerator->getRelationData($table, 'id');
            $playbook = $playbookGenerator->createSql($hydratedData, $table, 'version_id', Defaults::LIVE_VERSION);

            foreach ($playbook as $query) {
                $connection->executeStatement($query);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to do here
    }
}
