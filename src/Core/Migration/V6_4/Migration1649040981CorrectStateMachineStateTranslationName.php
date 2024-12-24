<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1649040981CorrectStateMachineStateTranslationName extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1649040981;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'UPDATE state_machine_state_translation SET name = :expectName WHERE name = :actualName',
            ['expectName' => 'In Progress', 'actualName' => 'In progress']
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
