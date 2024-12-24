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
class Migration1559050903PromotionExclusion extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1559050903;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `promotion` ADD `exclusion_ids` JSON  NULL;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
