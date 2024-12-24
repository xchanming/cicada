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
class Migration1571981437AddSeoColumns extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571981437;
    }

    public function update(Connection $connection): void
    {
        $this->addProductColum($connection);

        $this->addCategoryColumns($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product_translation`
            DROP COLUMN `additional_text`
        ');
    }

    private function addProductColum(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product_translation`
            ADD COLUMN `meta_description` varchar(255) NULL AFTER `additional_text`
        ');
        $connection->executeStatement('
            UPDATE `product_translation`
            SET `meta_description` = `additional_text`;
        ');
    }

    private function addCategoryColumns(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `category_translation`
            ADD COLUMN `meta_title` varchar(255) NULL AFTER `description`
        ');

        $connection->executeStatement('
            ALTER TABLE `category_translation`
            ADD COLUMN `meta_description` varchar(255) NULL AFTER `meta_title`
        ');

        $connection->executeStatement('
            ALTER TABLE `category_translation`
            ADD COLUMN `keywords` varchar(255) NULL AFTER `meta_description`
        ');
    }
}
