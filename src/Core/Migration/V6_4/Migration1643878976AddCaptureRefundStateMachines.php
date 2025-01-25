<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureStates;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStates;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Migration\Traits\StateMachineMigration;
use Cicada\Core\Migration\Traits\StateMachineMigrationTrait;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1643878976AddCaptureRefundStateMachines extends MigrationStep
{
    use StateMachineMigrationTrait;

    public function getCreationTimestamp(): int
    {
        return 1643878976;
    }

    public function update(Connection $connection): void
    {
        $this->import($this->captureStateMachine(), $connection);
        $this->import($this->captureRefundStateMachine(), $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function captureStateMachine(): StateMachineMigration
    {
        return new StateMachineMigration(
            OrderTransactionCaptureStates::STATE_MACHINE,
            '订单扣款状态',
            'Capture state',
            [
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_PENDING,
                    '处理中',
                    'Pending'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_COMPLETED,
                    '完成',
                    'Complete'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_FAILED,
                    '失败',
                    'Failed',
                ),
            ],
            [
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureStates::STATE_PENDING,
                    OrderTransactionCaptureStates::STATE_COMPLETED
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureStates::STATE_PENDING,
                    OrderTransactionCaptureStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureStates::STATE_COMPLETED,
                    OrderTransactionCaptureStates::STATE_PENDING,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureStates::STATE_FAILED,
                    OrderTransactionCaptureStates::STATE_PENDING,
                ),
            ],
            OrderTransactionCaptureStates::STATE_PENDING
        );
    }

    private function captureRefundStateMachine(): StateMachineMigration
    {
        return new StateMachineMigration(
            OrderTransactionCaptureRefundStates::STATE_MACHINE,
            '退款状态',
            'Refund state',
            [
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    '待处理',
                    'Open'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    '处理中',
                    'In progress'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                    '完成',
                    'Completed',
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                    '失败',
                    'Failed'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                    '已取消',
                    'Cancelled'
                ),
            ],
            [
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_PROCESS,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_CANCEL,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_CANCEL,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
            ],
            OrderTransactionCaptureRefundStates::STATE_OPEN
        );
    }
}
