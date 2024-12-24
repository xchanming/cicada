<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * Corrects the columns of migration \Cicada\Core\Migration\V6_5\Migration1698682149MakeTranslatableFieldsNullable
 *
 * @internal
 */
#[Package('core')]
class Migration1701337056CorrectColumnLength extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1701337056;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `country_translation` MODIFY `address_format` JSON DEFAULT NULL;');
        $connection->executeStatement('ALTER TABLE `number_range_type_translation` MODIFY `type_name` VARCHAR(64) DEFAULT NULL;');
    }
}
