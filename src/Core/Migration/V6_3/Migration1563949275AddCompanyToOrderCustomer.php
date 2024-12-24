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
class Migration1563949275AddCompanyToOrderCustomer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1563949275;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `order_customer` ADD `company` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL AFTER `title`');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
