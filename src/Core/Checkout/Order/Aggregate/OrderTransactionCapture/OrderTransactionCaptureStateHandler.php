<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Cicada\Core\System\StateMachine\Exception\IllegalTransitionException;
use Cicada\Core\System\StateMachine\StateMachineException;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use Cicada\Core\System\StateMachine\Transition;

#[Package('checkout')]
class OrderTransactionCaptureStateHandler
{
    /**
     * @internal
     */
    public function __construct(private readonly StateMachineRegistry $stateMachineRegistry)
    {
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineException
     * @throws IllegalTransitionException
     */
    public function complete(string $transactionCaptureId, Context $context): void
    {
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionCaptureDefinition::ENTITY_NAME,
                $transactionCaptureId,
                StateMachineTransitionActions::ACTION_COMPLETE,
                'stateId'
            ),
            $context
        );
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineException
     * @throws IllegalTransitionException
     */
    public function fail(string $transactionCaptureId, Context $context): void
    {
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionCaptureDefinition::ENTITY_NAME,
                $transactionCaptureId,
                StateMachineTransitionActions::ACTION_FAIL,
                'stateId'
            ),
            $context
        );
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineException
     * @throws IllegalTransitionException
     */
    public function reopen(string $transactionCaptureId, Context $context): void
    {
        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionCaptureDefinition::ENTITY_NAME,
                $transactionCaptureId,
                StateMachineTransitionActions::ACTION_REOPEN,
                'stateId'
            ),
            $context
        );
    }
}
