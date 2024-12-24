<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Payment\Response\AbstractResponse;
use Cicada\Core\Framework\App\Payment\Response\PaymentResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentResponse::class)]
#[CoversClass(AbstractResponse::class)]
class PaymentResponseTest extends TestCase
{
    public function testResponse(): void
    {
        $response = PaymentResponse::create([
            'status' => StateMachineTransitionActions::ACTION_PAID,
            'message' => 'test message',
            'redirectUrl' => 'http://test.com',
        ]);

        static::assertSame(StateMachineTransitionActions::ACTION_PAID, $response->getStatus());
        static::assertSame('test message', $response->getErrorMessage());
        static::assertSame('http://test.com', $response->getRedirectUrl());
    }

    public function testFailMessage(): void
    {
        $response = PaymentResponse::create([
            'status' => StateMachineTransitionActions::ACTION_FAIL,
        ]);

        static::assertSame('Payment was reported as failed.', $response->getErrorMessage());
    }
}
