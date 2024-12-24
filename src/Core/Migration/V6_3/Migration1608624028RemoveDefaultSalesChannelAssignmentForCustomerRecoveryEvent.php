<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1608624028RemoveDefaultSalesChannelAssignmentForCustomerRecoveryEvent extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1608624028;
    }

    public function update(Connection $connection): void
    {
        $customerRecoveryEvents = $connection->fetchAllAssociative('
            SELECT id FROM `event_action`
            WHERE event_name = "customer.recovery.request"
            AND action_name = "action.mail.send"
            AND updated_at IS NULL;
        ');

        if (empty($customerRecoveryEvents)) {
            return;
        }

        $customerRecoveryEvents = array_map(fn ($event) => $event['id'], $customerRecoveryEvents);

        try {
            $connection->executeStatement(
                'DELETE FROM event_action_sales_channel WHERE event_action_id IN (:eventActionIds)',
                ['eventActionIds' => $customerRecoveryEvents],
                ['eventActionIds' => ArrayParameterType::BINARY]
            );
        } catch (\Exception) {
            // nth
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
