<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1592466717AddKeywordIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1592466717;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE `product_search_keyword` ADD INDEX `idx.product_search_keyword.keyword_language` (`keyword`, `language_id`);');
        } catch (Exception) {
            // index already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
