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
class Migration1611155140AddUpdatedAtToSalesChannelApiContext extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1611155140;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                'ALTER TABLE `sales_channel_api_context`
                ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            );
        } catch (\Throwable) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
