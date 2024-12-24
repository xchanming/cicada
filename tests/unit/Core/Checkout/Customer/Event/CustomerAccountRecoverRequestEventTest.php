<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;

/**
 * @internal
 */
#[CoversClass(CustomerAccountRecoverRequestEvent::class)]
class CustomerAccountRecoverRequestEventTest extends TestCase
{
    public function testRestoreScalarValuesCorrectly(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setTranslated(['name' => 'my-shop-name']);

        $context = $this->createMock(SalesChannelContext::class);
        $context->expects(static::any())->method('getSalesChannel')->willReturn($salesChannel);

        $event = new CustomerAccountRecoverRequestEvent(
            $context,
            new CustomerRecoveryEntity(),
            'my-reset-url'
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('resetUrl', $flow->data());
        static::assertArrayHasKey('shopName', $flow->data());
        static::assertEquals('my-reset-url', $flow->data()['resetUrl']);
        static::assertEquals('my-shop-name', $flow->data()['shopName']);
    }
}
