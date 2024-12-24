<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1667983492UpdateQueuedTasksToSkipped extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1667983492;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'UPDATE `scheduled_task` SET `status` = :skippedStatus, next_execution_time = :nextExecutionTime
                WHERE `status` = :queuedStatus AND `name` IN (:skippedTasks)',
            [
                'skippedStatus' => ScheduledTaskDefinition::STATUS_SKIPPED,
                'queuedStatus' => ScheduledTaskDefinition::STATUS_QUEUED,
                'skippedTasks' => ['cicada.invalidate_cache', 'cicada.elasticsearch.create.alias'],
                'nextExecutionTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'skippedTasks' => ArrayParameterType::STRING,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
