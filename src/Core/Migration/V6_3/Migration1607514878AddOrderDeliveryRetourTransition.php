<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1607514878AddOrderDeliveryRetourTransition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1607514878;
    }

    public function update(Connection $connection): void
    {
        $stateMachine = $connection->fetchOne('SELECT id FROM state_machine WHERE technical_name = :name', ['name' => 'order_delivery.state']);
        if (!$stateMachine) {
            return;
        }

        $returnedPartially = $connection->fetchOne('SELECT id FROM state_machine_state WHERE technical_name = :name AND state_machine_id = :id', ['name' => 'returned_partially', 'id' => $stateMachine]);

        if (!$returnedPartially) {
            return;
        }

        $returned = $connection->fetchOne('SELECT id FROM state_machine_state WHERE technical_name = :name AND state_machine_id = :id', ['name' => 'returned', 'id' => $stateMachine]);

        if (!$returned) {
            return;
        }

        $existedRetourTransition = $connection->fetchOne('
            SELECT `id` FROM `state_machine_transition`
            WHERE `action_name` = :actionName
            AND `state_machine_id` = :stateMachineId
            AND `from_state_id` = :fromStateId
            AND `to_state_id` = :toStateId;
        ', [
            'actionName' => 'retour',
            'stateMachineId' => $stateMachine,
            'fromStateId' => $returnedPartially,
            'toStateId' => $returned,
        ]);

        if ($existedRetourTransition) {
            return;
        }

        $connection->insert('state_machine_transition', [
            'id' => Uuid::randomBytes(),
            'action_name' => 'retour',
            'state_machine_id' => $stateMachine,
            'from_state_id' => $returnedPartially,
            'to_state_id' => $returned,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
