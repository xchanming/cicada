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
class Migration1583416186KeywordUniques extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1583416186;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('TRUNCATE product_keyword_dictionary');

        $connection->executeStatement('ALTER TABLE `product_keyword_dictionary` ADD UNIQUE `uniq.language_id_keyword` (`language_id`, `keyword`);');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
