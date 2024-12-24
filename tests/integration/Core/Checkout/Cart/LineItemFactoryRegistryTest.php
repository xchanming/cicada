<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryRegistry;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Content\Product\Cart\ProductCartProcessor;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class LineItemFactoryRegistryTest extends TestCase
{
    use IntegrationTestBehaviour;

    private LineItemFactoryRegistry $service;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        $this->service = static::getContainer()->get(LineItemFactoryRegistry::class);
        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    public function testCreateProduct(): void
    {
        $lineItem = $this->service->create(['type' => 'product', 'referencedId' => 'test'], $this->context);
        static::assertSame('test', $lineItem->getReferencedId());
        static::assertSame(LineItem::PRODUCT_LINE_ITEM_TYPE, $lineItem->getType());
        static::assertSame(1, $lineItem->getQuantity());
    }

    public function testCreateProductWithPriceDefinition(): void
    {
        $this->expectException(CartException::class);

        $this->service->create([
            'type' => 'product',
            'referencedId' => 'test',
            'priceDefinition' => [
                'price' => 100.0,
                'type' => 'quantity',
                'precision' => 1,
                'taxRules' => [
                    [
                        'taxRate' => 5,
                        'percentage' => 100,
                    ],
                ],
            ],
        ], $this->context);
    }

    public function testCreateProductWithPriceDefinitionWithPermissions(): void
    {
        $this->context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $lineItem = $this->service->create([
            'type' => 'product',
            'referencedId' => 'test',
            'priceDefinition' => [
                'price' => 100.0,
                'type' => 'quantity',
                'precision' => 1,
                'taxRules' => [
                    [
                        'taxRate' => 5,
                        'percentage' => 100,
                    ],
                ],
            ],
        ], $this->context);

        static::assertSame('test', $lineItem->getReferencedId());
        static::assertSame(LineItem::PRODUCT_LINE_ITEM_TYPE, $lineItem->getType());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertInstanceOf(QuantityPriceDefinition::class, $lineItem->getPriceDefinition());
        static::assertSame(100.0, $lineItem->getPriceDefinition()->getPrice());
    }

    public function testUpdateDisabledStackable(): void
    {
        $id = Uuid::randomHex();
        $lineItem = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex(), 1);
        $lineItem->setStackable(false);

        $cart = new Cart('test');
        $cart->add($lineItem);

        $this->expectException(CartException::class);

        $this->service->update($cart, ['id' => $id, 'quantity' => 2], $this->context);
    }

    public function testChangeQuantity(): void
    {
        $id = Uuid::randomHex();
        $lineItem = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex(), 1);
        $lineItem->setStackable(true);

        $cart = new Cart('test');
        $cart->add($lineItem);

        $this->service->update($cart, ['id' => $id, 'quantity' => 2], $this->context);
        static::assertSame(2, $lineItem->getQuantity());
    }

    public function testCreatePromotion(): void
    {
        $lineItem = $this->service->create(['type' => 'promotion', 'referencedId' => 'test'], $this->context);
        static::assertSame('test', $lineItem->getReferencedId());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertSame(LineItem::PROMOTION_LINE_ITEM_TYPE, $lineItem->getType());
    }

    public function testCreateCustomWithoutPermission(): void
    {
        $this->expectException(CartException::class);

        $this->service->create(['type' => 'custom', 'referencedId' => 'test'], $this->context);
    }

    public function testCreateWithPermission(): void
    {
        $this->context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $lineItem = $this->service->create([
            'type' => 'custom',
            'referencedId' => 'test',
        ], $this->context);

        static::assertSame('custom', $lineItem->getType());
        static::assertSame('test', $lineItem->getReferencedId());
    }
}
