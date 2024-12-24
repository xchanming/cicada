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
class Migration1565346846Promotion extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1565346846;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `promotion` ADD `use_setgroups` TINYINT(1) NOT NULL DEFAULT 0;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
