<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartContextHasher;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\OrderPersister;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\SalesChannel\CartOrderRoute;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\TaxProvider\TaxProviderProcessor;
use Cicada\Core\Checkout\Gateway\SalesChannel\AbstractCheckoutGatewayRoute;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\PaymentProcessor;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CartOrderRoute::class)]
class CartOrderRouteTest extends TestCase
{
    private CartCalculator&MockObject $cartCalculator;

    private EntityRepository&MockObject $orderRepository;

    private OrderPersister&MockObject $orderPersister;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private CartContextHasher $cartContextHasher;

    private SalesChannelContext $context;

    private CartOrderRoute $route;

    protected function setUp(): void
    {
        $this->cartCalculator = $this->createMock(CartCalculator::class);
        $this->orderRepository = $this->createMock(EntityRepository::class);
        $this->orderPersister = $this->createMock(OrderPersister::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cartContextHasher = new CartContextHasher(new EventDispatcher());

        $this->route = new CartOrderRoute(
            $this->cartCalculator,
            $this->orderRepository,
            $this->orderPersister,
            $this->createMock(AbstractCartPersister::class),
            $this->eventDispatcher,
            $this->createMock(PaymentProcessor::class),
            $this->createMock(TaxProviderProcessor::class),
            $this->createMock(AbstractCheckoutGatewayRoute::class),
            $this->cartContextHasher
        );

        $this->context = Generator::createSalesChannelContext();
    }

    public function testOrderResponseWithoutHash(): void
    {
        $cartPrice = new CartPrice(
            15,
            20,
            1,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        $cart = new Cart('token');
        $cart->setPrice($cartPrice);
        $cart->add(new LineItem('id', 'type'));

        $data = new RequestDataBag();

        $calculatedCart = new Cart('calculated');

        $this->cartCalculator->expects(static::once())
            ->method('calculate')
            ->with($cart, $this->context)
            ->willReturn($calculatedCart);

        $orderID = 'oder-ID';

        $this->orderPersister->expects(static::once())
            ->method('persist')
            ->with($calculatedCart, $this->context)
            ->willReturn($orderID);

        $orderEntityMock = $this->createMock(EntitySearchResult::class);

        $orderEntity = new OrderEntity();

        $this->orderRepository->expects(static::once())
            ->method('search')
            ->willReturn($orderEntityMock);

        $orderEntityMock->expects(static::once())
            ->method('first')
            ->willReturn($orderEntity);

        $response = $this->route->order($cart, $this->context, $data);

        static::assertInstanceOf(OrderEntity::class, $response->getObject());
        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testCheckoutOrderPlacedEventsDispatched(): void
    {
        $cartPrice = new CartPrice(
            15,
            20,
            1,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        $cart = new Cart('token');
        $cart->setPrice($cartPrice);
        $cart->add(new LineItem('id', 'type'));

        $data = new RequestDataBag();

        $calculatedCart = new Cart('calculated');

        $this->cartCalculator->expects(static::once())
            ->method('calculate')
            ->with($cart, $this->context)
            ->willReturn($calculatedCart);

        $orderID = 'oder-ID';

        $this->orderPersister->expects(static::once())
            ->method('persist')
            ->with($calculatedCart, $this->context)
            ->willReturn($orderID);

        $orderEntityMock = $this->createMock(EntitySearchResult::class);

        $orderEntity = new OrderEntity();

        $this->orderRepository->expects(static::once())
            ->method('search')
            ->willReturn($orderEntityMock);

        $orderEntityMock->expects(static::once())
            ->method('first')
            ->willReturn($orderEntity);

        $this->eventDispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->with(static::callback(static function ($event) use ($orderID, $orderEntity) {
                if ($event instanceof CheckoutOrderPlacedCriteriaEvent) {
                    return $event->getCriteria()->getIds() === [$orderID];
                }
                if ($event instanceof CheckoutOrderPlacedEvent) {
                    return $event->getOrder() === $orderEntity;
                }

                return false;
            }));

        $response = $this->route->order($cart, $this->context, $data);

        static::assertInstanceOf(OrderEntity::class, $response->getObject());
        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testOrderResponseWithValidHash(): void
    {
        $cartPrice = new CartPrice(
            15,
            20,
            1,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        $cart = new Cart('token');
        $cart->setPrice($cartPrice);
        $cart->add(new LineItem('id', 'type'));
        $cart->setHash($this->cartContextHasher->generate($cart, $this->context));

        $data = new RequestDataBag();
        $data->set('hash', $cart->getHash());

        $calculatedCart = new Cart('calculated');

        $this->cartCalculator->expects(static::once())
            ->method('calculate')
            ->with($cart, $this->context)
            ->willReturn($calculatedCart);

        $orderID = 'oder-ID';

        $this->orderPersister->expects(static::once())
            ->method('persist')
            ->with($calculatedCart, $this->context)
            ->willReturn($orderID);

        $orderEntityMock = $this->createMock(EntitySearchResult::class);

        $orderEntity = new OrderEntity();

        $this->orderRepository->expects(static::once())
            ->method('search')
            ->willReturn($orderEntityMock);

        $orderEntityMock->expects(static::once())
            ->method('first')
            ->willReturn($orderEntity);

        $response = $this->route->order($cart, $this->context, $data);

        static::assertInstanceOf(OrderEntity::class, $response->getObject());
        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testHashMismatchException(): void
    {
        $cartPrice = new CartPrice(
            15,
            20,
            1,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        $cart = new Cart('token');
        $cart->setPrice($cartPrice);
        $cart->add(new LineItem('1', 'type'));

        $lineItem = new LineItem('1', 'type');
        $lineItem->addChild(new LineItem('1', 'type'));

        $cartPrice2 = new CartPrice(
            20,
            25,
            1,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        $cart2 = new Cart('token2');
        $cart2->setPrice($cartPrice2);
        $cart2->add($lineItem);
        $cart2->add(new LineItem('2', 'type'));

        $data = new RequestDataBag();
        $data->set('hash', $this->cartContextHasher->generate($cart2, $this->context));

        static::expectException(CartException::class);

        $this->route->order($cart, $this->context, $data);
    }
}
