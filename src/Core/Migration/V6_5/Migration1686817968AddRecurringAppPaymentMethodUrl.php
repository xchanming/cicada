<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1686817968AddRecurringAppPaymentMethodUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1686817968;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'app_payment_method',
            column: 'recurring_url',
            type: 'VARCHAR(255)'
        );
    }
}
