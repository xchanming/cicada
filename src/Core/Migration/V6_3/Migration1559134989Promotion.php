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
class Migration1559134989Promotion extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1559134989;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion` ADD `use_individual_codes` TINYINT(1) NOT NULL DEFAULT 0;');
        $connection->executeStatement('ALTER TABLE `promotion` ADD `individual_code_pattern` VARCHAR(255) NULL UNIQUE;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
