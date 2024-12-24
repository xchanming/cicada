<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\OrderConversionContext;
use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Cart\Order\RecalculationService;
use Cicada\Core\Checkout\Cart\Order\Transformer\CartTransformer;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Processor;
use Cicada\Core\Checkout\Cart\RuleLoaderResult;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

/**
 * @internal
 */
#[CoversClass(RecalculationService::class)]
class RecalculationServiceTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    private OrderConverter&MockObject $orderConverter;

    private CartRuleLoader&MockObject $cartRuleLoader;

    private Context $context;

    protected function setUp(): void
    {
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
        $this->orderConverter = $this->createMock(OrderConverter::class);
        $this->orderConverter
            ->method('assembleSalesChannelContext')
            ->willReturnCallback(function (OrderEntity $order, Context $context) {
                $context->setTaxState($order->getTaxStatus());

                $salesChannel = new SalesChannelEntity();
                $salesChannel->setId(Uuid::randomHex());

                return Generator::createSalesChannelContext(
                    baseContext: $context,
                    salesChannel: $salesChannel
                );
            });

        $this->cartRuleLoader = $this->createMock(CartRuleLoader::class);
        $this->context = Context::createDefaultContext();
    }

    public function testRecalculateOrderWithTaxStatus(): void
    {
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::CUSTOM_LINE_ITEM_TYPE);

        $deliveryEntity = new OrderDeliveryEntity();
        $deliveryEntity->setId(Uuid::randomHex());
        $deliveryEntity->setStateId(Uuid::randomHex());

        $deliveries = new OrderDeliveryCollection([$deliveryEntity]);

        $orderEntity = $this->orderEntity();
        $orderEntity->setDeliveries($deliveries);
        $cart = $this->getCart();
        $cart->add($lineItem);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$orderEntity]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data, Context $context) use ($orderEntity) {
                static::assertSame($data[0]['stateId'], $orderEntity->getStateId());
                static::assertNotNull($data[0]['deliveries']);
                static::assertNotNull($data[0]['deliveries'][0]);
                static::assertSame($data[0]['deliveries'][0]['stateId'], $orderEntity->getDeliveries()?->first()?->getStateId());

                static::assertSame($context->getTaxState(), CartPrice::TAX_STATE_FREE);

                $price = $data[0]['price'];
                self::assertInstanceOf(CartPrice::class, $price);

                static::assertSame($price->getTaxStatus(), CartPrice::TAX_STATE_FREE);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], Context::createDefaultContext()),
                ]), []);
            });

        $this->orderConverter
            ->expects(static::once())
            ->method('convertToCart')
            ->willReturnCallback(function (OrderEntity $order, Context $context) use ($cart) {
                static::assertSame($order->getTaxStatus(), CartPrice::TAX_STATE_FREE);
                static::assertSame($context->getTaxState(), CartPrice::TAX_STATE_FREE);

                return $cart;
            });

        $this->orderConverter
            ->expects(static::once())
            ->method('convertToOrder')
            ->willReturnCallback(function (Cart $cart, SalesChannelContext $context, OrderConversionContext $conversionContext) {
                $salesChannelContext = $this->createMock(SalesChannelContext::class);
                $salesChannelContext->method('getTaxState')
                    ->willReturn(CartPrice::TAX_STATE_FREE);

                return CartTransformer::transform(
                    $cart,
                    $salesChannelContext,
                    '',
                    $conversionContext->shouldIncludeOrderDate()
                );
            });

        $this->cartRuleLoader
            ->expects(static::once())
            ->method('loadByCart')
            ->willReturn(
                new RuleLoaderResult(
                    $cart,
                    new RuleCollection()
                )
            );

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->recalculateOrder($orderEntity->getId(), $this->context);
    }

    public function testAddProductToOrder(): void
    {
        $deliveryEntity = new OrderDeliveryEntity();
        $deliveryEntity->setId(Uuid::randomHex());
        $deliveryEntity->setStateId(Uuid::randomHex());

        $deliveries = new OrderDeliveryCollection([$deliveryEntity]);

        $order = $this->orderEntity();
        $order->setDeliveries($deliveries);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data) use ($order) {
                static::assertSame($data[0]['stateId'], $order->getStateId());
                static::assertNotNull($data[0]['deliveries']);
                static::assertNotNull($data[0]['deliveries'][0]);
                static::assertSame($data[0]['deliveries'][0]['stateId'], $order->getDeliveries()?->first()?->getStateId());

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], $this->context),
                ]), []);
            });

        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());

        $productRepository = new StaticEntityRepository([
            new ProductCollection([$productEntity]),
        ]);

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $productRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->addProductToOrder($order->getId(), $productEntity->getId(), 1, $this->context);
    }

    public function testAddCustomLineItem(): void
    {
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::CUSTOM_LINE_ITEM_TYPE);

        $order = $this->orderEntity();
        $cart = $this->getCart();
        $cart->add($lineItem);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data) use ($order) {
                static::assertSame($data[0]['stateId'], $order->getStateId());

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], $this->context),
                ]), []);
            });

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->addCustomLineItem($order->getId(), $lineItem, $this->context);
    }

    public function testAssertProcessorsCalledWithLiveVersion(): void
    {
        $deliveryEntity = new OrderDeliveryEntity();
        $deliveryEntity->setId(Uuid::randomHex());
        $deliveryEntity->setStateId(Uuid::randomHex());

        $deliveries = new OrderDeliveryCollection([$deliveryEntity]);

        $order = $this->orderEntity();
        $order->setDeliveries($deliveries);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data) use ($order) {
                static::assertSame($data[0]['stateId'], $order->getStateId());
                static::assertNotNull($data[0]['deliveries']);
                static::assertNotNull($data[0]['deliveries'][0]);
                static::assertSame($data[0]['deliveries'][0]['stateId'], $order->getDeliveries()?->first()?->getStateId());

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], $this->context),
                ]), []);
            });

        $productEntity = new ProductEntity();
        $productEntity->setId(Uuid::randomHex());

        $productRepository = new StaticEntityRepository([
            new ProductCollection([$productEntity]),
        ]);

        $processor = new LiveProcessorValidator();

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $productRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $processor,
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->addProductToOrder($order->getId(), $productEntity->getId(), 1, $this->context);

        static::assertSame(Defaults::LIVE_VERSION, $processor->versionId);
    }

    public function testAddPromotionLineItem(): void
    {
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::CUSTOM_LINE_ITEM_TYPE);

        $order = $this->orderEntity();
        $cart = $this->getCart();
        $cart->add($lineItem);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data) use ($order) {
                static::assertSame($data[0]['stateId'], $order->getStateId());

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], $this->context),
                ]), []);
            });

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->addPromotionLineItem($order->getId(), '', $this->context);
    }

    public function testToggleAutomaticPromotion(): void
    {
        $order = $this->orderEntity();

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert');

        $this->orderConverter
            ->expects(static::once())
            ->method('convertToOrder')
            ->with(static::anything(), static::anything(), static::callback(static function (OrderConversionContext $context) {
                return $context->shouldIncludeDeliveries();
            }));

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->toggleAutomaticPromotion($order->getId(), $this->context, false);
    }

    public function testRecalculateOrderWithEmptyLineItems(): void
    {
        $orderEntity = $this->orderEntity();

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('search')->willReturnOnConsecutiveCalls(
            new EntitySearchResult('order', 1, new OrderCollection([$orderEntity]), null, new Criteria(), $this->salesChannelContext->getContext()),
        );

        $entityRepository
            ->expects(static::once())
            ->method('upsert')
            ->willReturnCallback(function (array $data) {
                static::assertNotNull($data[0]);
                static::assertEmpty($data[0]['deliveries']);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
                    new EntityWrittenEvent('order', [new EntityWriteResult('created-id', [], 'order', EntityWriteResult::OPERATION_INSERT)], Context::createDefaultContext()),
                ]), []);
            });

        $this->orderConverter
            ->expects(static::once())
            ->method('convertToOrder')
            ->willReturnCallback(function (Cart $cart, SalesChannelContext $context, OrderConversionContext $conversionContext) {
                $salesChannelContext = $this->createMock(SalesChannelContext::class);
                $salesChannelContext->method('getTaxState')
                    ->willReturn(CartPrice::TAX_STATE_FREE);

                return CartTransformer::transform(
                    $cart,
                    $salesChannelContext,
                    '',
                    $conversionContext->shouldIncludeOrderDate()
                );
            });

        $recalculationService = new RecalculationService(
            $entityRepository,
            $this->orderConverter,
            $this->createMock(CartService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(Processor::class),
            $this->cartRuleLoader,
            $this->createMock(PromotionItemBuilder::class)
        );

        $recalculationService->recalculateOrder($orderEntity->getId(), $this->context);
    }

    private function orderEntity(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setSalesChannelId(Uuid::randomHex());
        $order->setTaxStatus(CartPrice::TAX_STATE_FREE);
        $order->setStateId(Uuid::randomHex());

        return $order;
    }

    private function getCart(): Cart
    {
        $cart = new Cart(Uuid::randomHex());

        $cart->setPrice(new CartPrice(
            0.0,
            0.0,
            0.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        ));

        return $cart;
    }
}

/**
 * @internal
 */
class LiveProcessorValidator extends Processor
{
    public ?string $versionId = null;

    public function __construct()
    {
    }

    public function process(Cart $original, SalesChannelContext $context, CartBehavior $behavior): Cart
    {
        TestCase::assertSame(Defaults::LIVE_VERSION, $context->getVersionId());
        $this->versionId = $context->getVersionId();

        return $original;
    }
}
