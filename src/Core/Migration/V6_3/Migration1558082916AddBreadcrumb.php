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
class Migration1558082916AddBreadcrumb extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1558082916;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `category_translation` ADD `breadcrumb` json NULL AFTER `name`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
