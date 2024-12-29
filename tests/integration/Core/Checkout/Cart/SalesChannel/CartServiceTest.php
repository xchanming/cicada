<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\Event\AfterLineItemAddedEvent;
use Cicada\Core\Checkout\Cart\Event\AfterLineItemQuantityChangedEvent;
use Cicada\Core\Checkout\Cart\Event\AfterLineItemRemovedEvent;
use Cicada\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Cicada\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Cicada\Core\Checkout\Cart\Event\BeforeLineItemRemovedEvent;
use Cicada\Core\Checkout\Cart\Event\CartCreatedEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\CountryAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class CartServiceTest extends TestCase
{
    use CountryAddToSalesChannelTestBehaviour;
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use TaxAddToSalesChannelTestBehaviour;

    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $context = Context::createDefaultContext();
        $this->productId = Uuid::randomHex();
        $product = [
            'id' => $this->productId,
            'productNumber' => $this->productId,
            'name' => 'test',
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 100, 'net' => 100, 'linked' => false],
            ],
            'tax' => ['name' => 'test', 'taxRate' => 18],
            'manufacturer' => ['name' => 'test'],
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        static::getContainer()->get('product.repository')
            ->create([$product], $context);
    }

    public function testCreateNewWithEvent(): void
    {
        $caughtEvent = null;
        $this->addEventListener(static::getContainer()->get('event_dispatcher'), CartCreatedEvent::class, static function (CartCreatedEvent $event) use (&$caughtEvent): void {
            $caughtEvent = $event;
        });

        $cartService = static::getContainer()->get(CartService::class);

        $token = Uuid::randomHex();
        $newCart = $cartService->createNew($token);

        static::assertInstanceOf(CartCreatedEvent::class, $caughtEvent);
        static::assertSame($newCart, $caughtEvent->getCart());
        static::assertSame($newCart, $cartService->getCart($token, $this->getSalesChannelContext()));
        static::assertNotSame($newCart, $cartService->createNew($token));
    }

    public function testLineItemAddedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $isMerged = null;
        $this->addEventListener($dispatcher, BeforeLineItemAddedEvent::class, static function (BeforeLineItemAddedEvent $addedEvent) use (&$isMerged): void {
            $isMerged = $addedEvent->isMerged();
        });

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $cartId = Uuid::randomHex();
        $cart = $cartService->getCart($cartId, $context);
        $cartService->add(
            $cart,
            (new LineItem('test', 'test'))->setStackable(true),
            $context
        );

        static::assertNotNull($isMerged);
        static::assertFalse($isMerged);

        $cartService->add(
            $cart,
            new LineItem('test', 'test'),
            $context
        );

        /** @phpstan-ignore-next-line */
        static::assertTrue($isMerged);
    }

    public function testAfterLineItemAddedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->addEventListener($dispatcher, AfterLineItemAddedEvent::class, $listener);

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $cartId = Uuid::randomHex();
        $cart = $cartService->getCart($cartId, $context);
        $cartService->add(
            $cart,
            new LineItem('test', 'test'),
            $context
        );
    }

    public function testLineItemRemovedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->addEventListener($dispatcher, BeforeLineItemRemovedEvent::class, $listener);

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $this->productId, 'referencedId' => $this->productId], $context);

        $cart = $cartService->getCart($context->getToken(), $context);

        $cart = $cartService->add($cart, $lineItem, $context);

        static::assertTrue($cart->has($this->productId));

        $cart = $cartService->remove($cart, $this->productId, $context);

        static::assertFalse($cart->has($this->productId));
    }

    public function testAfterLineItemRemovedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->addEventListener($dispatcher, AfterLineItemRemovedEvent::class, $listener);

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $this->productId, 'referencedId' => $this->productId], $context);

        $cart = $cartService->getCart($context->getToken(), $context);

        $cart = $cartService->add($cart, $lineItem, $context);

        static::assertTrue($cart->has($this->productId));

        $cart = $cartService->remove($cart, $this->productId, $context);

        static::assertFalse($cart->has($this->productId));
    }

    public function testLineItemQuantityChangedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->addEventListener($dispatcher, BeforeLineItemQuantityChangedEvent::class, $listener);

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $this->productId, 'referencedId' => $this->productId], $context);

        $cart = $cartService->getCart($context->getToken(), $context);

        $cart = $cartService->add($cart, $lineItem, $context);

        static::assertTrue($cart->has($this->productId));

        $cartService->changeQuantity($cart, $this->productId, 100, $context);
    }

    public function testAfterLineItemQuantityChangedEventFired(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->addEventListener($dispatcher, AfterLineItemQuantityChangedEvent::class, $listener);

        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $this->productId, 'referencedId' => $this->productId], $context);

        $cart = $cartService->getCart($context->getToken(), $context);

        $cart = $cartService->add($cart, $lineItem, $context);

        static::assertTrue($cart->has($this->productId));

        $cartService->changeQuantity($cart, $this->productId, 100, $context);
    }

    public function testLineItemAddAndUpdate(): void
    {
        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $productId = Uuid::randomHex();
        $product = [
            'id' => $productId,
            'productNumber' => $productId,
            'name' => 'test',
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 5, 'net' => 5, 'linked' => false],
            ],
            'tax' => ['id' => Uuid::randomHex(), 'name' => 'test', 'taxRate' => 18],
            'manufacturer' => ['name' => 'test'],
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        static::getContainer()->get('product.repository')
            ->create([$product], $context->getContext());
        $this->addTaxDataToSalesChannel($context, $product['tax']);

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $productId, 'referencedId' => $productId], $context);
        $cart = $cartService->getCart($context->getToken(), $context);
        $cart = $cartService->add($cart, $lineItem, $context);

        $lineItem = $cart->getLineItems()->get($productId);

        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertEquals(1, $lineItem->getQuantity());
        static::assertTrue($lineItem->isStackable());
        static::assertTrue($lineItem->isRemovable());

        $cart = $cartService->update($cart, ['foo' => [
            'id' => $productId,
            'quantity' => 20,
            'payload' => ['foo' => 'bar'],
            'stackable' => false,
            'removable' => false,
        ]], $context);

        static::assertEquals(20, $lineItem->getQuantity());
        static::assertTrue($lineItem->isStackable());
        static::assertTrue($lineItem->isRemovable());
        static::assertEquals('bar', $lineItem->getPayloadValue('foo'));
    }

    public function testRemoveLineItems(): void
    {
        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $productId1 = Uuid::randomHex();
        $productId2 = Uuid::randomHex();
        $productId3 = Uuid::randomHex();

        $products = [];
        foreach ([$productId1, $productId2, $productId3] as $productId) {
            $products[] = [
                'id' => $productId,
                'productNumber' => $productId,
                'name' => 'test',
                'stock' => 10,
                'price' => [
                    ['currencyId' => Defaults::CURRENCY, 'gross' => 5, 'net' => 5, 'linked' => false],
                ],
                'tax' => ['id' => Uuid::randomHex(), 'name' => 'test', 'taxRate' => 18],
                'manufacturer' => ['name' => 'test'],
                'active' => true,
                'visibilities' => [
                    ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ];
        }

        static::getContainer()->get('product.repository')
            ->create($products, $context->getContext());

        $lineItems = [];
        foreach ($products as $product) {
            $this->addTaxDataToSalesChannel($context, $product['tax']);

            $lineItems[] = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $product['id'], 'referencedId' => $product['id']], $context);
        }

        $cart = $cartService->getCart($context->getToken(), $context);
        $cart = $cartService->add($cart, $lineItems, $context);

        static::assertCount(3, $cart->getLineItems());

        $cart = $cartService->removeItems($cart, [
            $productId1,
            $productId2,
        ], $context);

        static::assertCount(1, $cart->getLineItems());

        $remainingLineItem = $cart->getLineItems()->get($productId3);
        static::assertInstanceOf(LineItem::class, $remainingLineItem);
        static::assertEquals($productId3, $remainingLineItem->getReferencedId());
    }

    public function testZeroPricedItemsCanBeAddedToCart(): void
    {
        $cartService = static::getContainer()->get(CartService::class);

        $context = $this->getSalesChannelContext();

        $productId = Uuid::randomHex();
        $product = [
            'id' => $productId,
            'productNumber' => $productId,
            'name' => 'test',
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 0, 'net' => 0, 'linked' => false],
            ],
            'tax' => ['id' => Uuid::randomHex(), 'name' => 'test', 'taxRate' => 18],
            'manufacturer' => ['name' => 'test'],
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        static::getContainer()->get('product.repository')
            ->create([$product], $context->getContext());
        $this->addTaxDataToSalesChannel($context, $product['tax']);

        $lineItem = (new ProductLineItemFactory(new PriceDefinitionFactory()))->create(['id' => $productId, 'referencedId' => $productId], $context);

        $cart = $cartService->getCart($context->getToken(), $context);

        $cart = $cartService->add($cart, $lineItem, $context);

        static::assertTrue($cart->has($productId));
        static::assertEquals(0, $cart->getPrice()->getTotalPrice());

        $calculatedLineItem = $cart->getLineItems()->get($productId);
        static::assertNotNull($calculatedLineItem);
        static::assertNotNull($calculatedLineItem->getPrice());
        static::assertEquals(0, $calculatedLineItem->getPrice()->getTotalPrice());

        $calculatedTaxes = $calculatedLineItem->getPrice()->getCalculatedTaxes();
        static::assertNotNull($calculatedTaxes);
        static::assertEquals(0, $calculatedTaxes->getAmount());
    }

    public function testCartCreatedWithGivenToken(): void
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);
        $context = $salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $token = Uuid::randomHex();
        $cartService = static::getContainer()->get(CartService::class);
        $cart = $cartService->getCart($token, $context);

        static::assertSame($token, $cart->getToken());
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $this->addCountriesToSalesChannel();

        return static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }
}
