<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DoubleOptInGuestOrderEvent::class)]
class DoubleOptInGuestOrderEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new DoubleOptInGuestOrderEvent(
            new CustomerEntity(),
            $this->createMock(SalesChannelContext::class),
            'my-confirm-url'
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('confirmUrl', $flow->data());
        static::assertEquals('my-confirm-url', $flow->data()['confirmUrl']);
    }
}
