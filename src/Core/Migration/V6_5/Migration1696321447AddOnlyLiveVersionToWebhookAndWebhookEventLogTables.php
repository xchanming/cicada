<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1696321447AddOnlyLiveVersionToWebhookAndWebhookEventLogTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1696321447;
    }

    public function update(Connection $connection): void
    {
        $this->addToWebhookTable($connection);
        $this->addToWebhookEventLogTable($connection);
    }

    private function addToWebhookTable(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'webhook',
            column: 'only_live_version',
            type: 'TINYINT(1) UNSIGNED',
            nullable: false,
            default: '0'
        );
    }

    private function addToWebhookEventLogTable(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'webhook_event_log',
            column: 'only_live_version',
            type: 'TINYINT(1) UNSIGNED',
            nullable: false,
            default: '0'
        );
    }
}
