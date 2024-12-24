<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\SalesChannel\CancelOrderRouteResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CancelOrderRouteResponse::class)]
class CancelOrderRouteResponseTest extends TestCase
{
    public function testPublicAPI(): void
    {
        $state = new StateMachineStateEntity();
        $response = new CancelOrderRouteResponse($state);

        static::assertSame($state, $response->getObject());
        static::assertSame($state, $response->getState());
    }
}
