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
class Migration1574258789ProductReviewLanguageKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1574258789;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product_review` DROP FOREIGN KEY `fk.product_review.language_id`');
        $connection->executeStatement('ALTER TABLE `product_review` ADD CONSTRAINT `fk.product_review.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
