<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1646817331AddCmsClassColumnCmsPage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646817331;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `cms_page`'), 'Field');

        // only execute when the column does not exist
        if (!\in_array('cms_page', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `cms_page` ADD `css_class` VARCHAR(255) NULL AFTER `locked`;');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
