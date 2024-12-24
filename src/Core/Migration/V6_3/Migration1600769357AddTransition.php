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
class Migration1600769357AddTransition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600769357;
    }

    public function update(Connection $connection): void
    {
        $stateMachine = $connection->fetchOne('SELECT id FROM state_machine WHERE technical_name = :name', ['name' => 'order_transaction.state']);
        if (!$stateMachine) {
            return;
        }

        $cancelled = $connection->fetchOne('SELECT id FROM state_machine_state WHERE technical_name = :name AND state_machine_id = :id', ['name' => 'cancelled', 'id' => $stateMachine]);
        if (!$cancelled) {
            return;
        }

        $paid = $connection->fetchOne('SELECT id FROM state_machine_state WHERE technical_name = :name AND state_machine_id = :id', ['name' => 'paid', 'id' => $stateMachine]);
        if (!$paid) {
            return;
        }

        $connection->insert('state_machine_transition', [
            'id' => Uuid::randomBytes(),
            'action_name' => 'paid',
            'state_machine_id' => $stateMachine,
            'from_state_id' => $cancelled,
            'to_state_id' => $paid,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
