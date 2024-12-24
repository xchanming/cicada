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
class Migration1600072779AddSearchKeywordsToProductTranslation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600072779;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product_translation`
            ADD COLUMN `search_keywords` JSON NULL DEFAULT NULL,
            ADD CONSTRAINT `json.product_translation.search_keywords` CHECK (JSON_VALID(`search_keywords`));
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
