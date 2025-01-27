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
#[Package('framework')]
class Migration1574925962FixTaxConstraint extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1574925962;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `tax_rule_type_translation` DROP FOREIGN KEY `fk.tax_rule_type_translation.tax_rule_type_id`
        ');

        $connection->executeStatement('
            ALTER TABLE `tax_rule_type_translation`
            ADD CONSTRAINT `fk.tax_rule_type_translation.tax_rule_type_id` FOREIGN KEY (`tax_rule_type_id`)
            REFERENCES `tax_rule_type` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
