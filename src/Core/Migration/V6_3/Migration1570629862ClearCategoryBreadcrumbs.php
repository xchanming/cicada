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
class Migration1570629862ClearCategoryBreadcrumbs extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1570629862;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE `category_translation` SET `breadcrumb` = NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
