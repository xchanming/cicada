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
class Migration1585126355AddOrderCommentField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1585126355;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order`
            ADD COLUMN `customer_comment` LONGTEXT COLLATE utf8mb4_unicode_ci NULL AFTER `campaign_code`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
