<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1676274910ChangeColumnTaxRateAllowThreeDecimal extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1676274910;
    }

    public function update(Connection $connection): void
    {
        $sqlUpdateToTaxTable = <<<'SQL'
            ALTER TABLE tax
            MODIFY COLUMN `tax_rate` DECIMAL(10,3);
        SQL;

        $connection->executeStatement($sqlUpdateToTaxTable);

        $sqlUpdateToTaxRuleTable = <<<'SQL'
            ALTER TABLE tax_rule
            MODIFY COLUMN `tax_rate` DOUBLE(10,3);
        SQL;

        $connection->executeStatement($sqlUpdateToTaxRuleTable);
    }
}
