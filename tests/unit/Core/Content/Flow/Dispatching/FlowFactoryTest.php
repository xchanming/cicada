<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Dispatching\FlowFactory;
use Cicada\Core\Content\Flow\Dispatching\Storer\OrderStorer;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(FlowFactory::class)]
class FlowFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $ids = new IdsCollection();
        $order = new OrderEntity();
        $order->setId($ids->get('orderId'));

        $context = Generator::createSalesChannelContext();

        $awareEvent = new CheckoutOrderPlacedEvent($context, $order);

        $orderStorer = new OrderStorer($this->createMock(EntityRepository::class), $this->createMock(EventDispatcherInterface::class));
        $flowFactory = new FlowFactory([$orderStorer]);
        $flow = $flowFactory->create($awareEvent);

        static::assertEquals($ids->get('orderId'), $flow->getStore('orderId'));
        static::assertInstanceOf(SystemSource::class, $flow->getContext()->getSource());
        static::assertEquals(Context::SYSTEM_SCOPE, $flow->getContext()->getScope());
    }

    public function testRestore(): void
    {
        $ids = new IdsCollection();
        $order = new OrderEntity();
        $order->setId($ids->get('orderId'));

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $entitySearchResult->expects(static::once())
            ->method('get')
            ->willReturn($order);

        $orderRepo = $this->createMock(EntityRepository::class);
        $orderRepo->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);

        $context = Generator::createSalesChannelContext();

        $awareEvent = new CheckoutOrderPlacedEvent($context, $order);

        $orderStorer = new OrderStorer($orderRepo, $this->createMock(EventDispatcherInterface::class));
        $flowFactory = new FlowFactory([$orderStorer]);

        $storedData = [
            'orderId' => $ids->get('orderId'),
            'additional_keys' => ['order'],
        ];
        $flow = $flowFactory->restore('checkout.order.placed', $awareEvent->getContext(), $storedData);

        static::assertInstanceOf(OrderEntity::class, $flow->getData('order'));
        static::assertEquals($ids->get('orderId'), $flow->getData('order')->getId());

        static::assertInstanceOf(SystemSource::class, $flow->getContext()->getSource());
        static::assertEquals(Context::SYSTEM_SCOPE, $flow->getContext()->getScope());
    }
}
