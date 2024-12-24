<?php declare(strict_types=1);

namespace Cicada\Core\System\StateMachine\Aggregation\StateMachineState;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<StateMachineStateEntity>
 */
#[Package('checkout')]
class StateMachineStateCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'state_machine_state_collection';
    }

    protected function getExpectedClass(): string
    {
        return StateMachineStateEntity::class;
    }
}
