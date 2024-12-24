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
class Migration1559050088Promotion extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1559050088;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion` ADD `customer_restriction` TINYINT(1) NOT NULL DEFAULT 0;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
