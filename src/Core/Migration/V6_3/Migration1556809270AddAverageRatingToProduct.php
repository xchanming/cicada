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
class Migration1556809270AddAverageRatingToProduct extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1556809270;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $connection->executeStatement('ALTER TABLE `product` ADD `rating_average` float NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
