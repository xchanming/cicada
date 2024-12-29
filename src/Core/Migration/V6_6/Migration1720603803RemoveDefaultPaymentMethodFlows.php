<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1720603803RemoveDefaultPaymentMethodFlows extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720603803;
    }

    public function update(Connection $connection): void
    {
        $connection->update(
            'flow',
            [
                'invalid' => 1,
                'active' => 0,
            ],
            ['event_name' => 'checkout.customer.changed-payment-method']
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->delete('flow', ['event_name' => 'checkout.customer.changed-payment-method']);
    }
}
