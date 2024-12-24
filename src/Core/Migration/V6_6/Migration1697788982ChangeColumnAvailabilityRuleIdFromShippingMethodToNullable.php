<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1697788982;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `shipping_method` MODIFY COLUMN `availability_rule_id` BINARY(16) DEFAULT NULL');
    }
}
