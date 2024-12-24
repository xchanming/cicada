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
class Migration1591272594AddGoogleAnalyticsAnonymizeIpColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1591272594;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE sales_channel_analytics
            ADD COLUMN anonymize_ip TINYINT(1) NOT NULL DEFAULT 1'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
