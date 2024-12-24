<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Payment\Response\AbstractResponse;
use Cicada\Core\Framework\App\Payment\Response\RefundResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(RefundResponse::class)]
#[CoversClass(AbstractResponse::class)]
class RefundResponseTest extends TestCase
{
    public function testResponse(): void
    {
        $response = new RefundResponse();
        $response->assign([
            'status' => StateMachineTransitionActions::ACTION_COMPLETE,
            'message' => 'test message',
        ]);

        static::assertSame(StateMachineTransitionActions::ACTION_COMPLETE, $response->getStatus());
        static::assertSame('test message', $response->getErrorMessage());
    }

    public function testFailMessage(): void
    {
        $response = new RefundResponse();
        $response->assign([
            'status' => StateMachineTransitionActions::ACTION_FAIL,
        ]);

        static::assertSame('Refund was reported as failed.', $response->getErrorMessage());
    }
}
