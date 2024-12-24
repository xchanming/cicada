<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Event;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomerLoginEvent::class)]
class CustomerLoginEventTest extends TestCase
{
    public function testRestoreScalarValuesCorrectly(): void
    {
        $event = new CustomerLoginEvent(
            $this->createMock(SalesChannelContext::class),
            new CustomerEntity(),
            'context-token'
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('contextToken', $flow->data());
        static::assertEquals('context-token', $flow->data()['contextToken']);
    }
}
