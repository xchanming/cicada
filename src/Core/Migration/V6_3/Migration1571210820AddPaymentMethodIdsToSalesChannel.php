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
class Migration1571210820AddPaymentMethodIdsToSalesChannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571210820;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sales_channel`
            ADD COLUMN `payment_method_ids` JSON NULL AFTER `mail_header_footer_id`,
            ADD CONSTRAINT `json.sales_channel.payment_method_ids` CHECK (JSON_VALID(`payment_method_ids`));
        ');

        $this->registerIndexer($connection, 'sales_channel.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
