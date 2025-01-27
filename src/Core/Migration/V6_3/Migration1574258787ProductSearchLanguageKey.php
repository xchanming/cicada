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
#[Package('framework')]
class Migration1574258787ProductSearchLanguageKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1574258787;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product_search_keyword` DROP FOREIGN KEY `fk.product_search_keyword.language_id`');
        $connection->executeStatement('ALTER TABLE `product_search_keyword` ADD CONSTRAINT `fk.product_search_keyword.language_id` FOREIGN KEY (`language_id`)
                  REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
