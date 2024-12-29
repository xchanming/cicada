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
class Migration1596441551CustomerGroupRegistration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1596441551;
    }

    public function update(Connection $connection): void
    {
        $this->updateCustomerTable($connection);
        $this->createTables($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function updateCustomerTable(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `customer`
ADD `requested_customer_group_id` binary(16) NULL AFTER `customer_group_id`;');

        $connection->executeStatement('ALTER TABLE `customer`
ADD INDEX `fk.customer.requested_customer_group_id` (`requested_customer_group_id`);');
    }

    private function createTables(Connection $connection): void
    {
        $connection->executeStatement('
ALTER TABLE `customer_group`
ADD `registration_active` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `display_gross`;
');
        $connection->executeStatement('
ALTER TABLE `customer_group_translation`
ADD `registration_title` varchar(255) NULL AFTER `custom_fields`,
ADD `registration_introduction` longtext NULL AFTER `registration_title`,
ADD `registration_only_company_registration` tinyint(1) NULL AFTER `registration_introduction`,
ADD `registration_seo_meta_description` longtext NULL AFTER `registration_only_company_registration`;
');
    }
}
