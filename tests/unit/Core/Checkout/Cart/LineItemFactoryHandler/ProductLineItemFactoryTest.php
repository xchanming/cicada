<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItemFactoryHandler;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Content\Product\Cart\ProductCartProcessor;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProductLineItemFactory::class)]
#[Package('checkout')]
class ProductLineItemFactoryTest extends TestCase
{
    public function testSupports(): void
    {
        $factory = new ProductLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
        );

        static::assertTrue($factory->supports('product'));
        static::assertFalse($factory->supports('credit'));
        static::assertFalse($factory->supports('custom'));
        static::assertFalse($factory->supports('promotion'));
        static::assertFalse($factory->supports('discount'));
        static::assertFalse($factory->supports('container'));
        static::assertFalse($factory->supports('foo'));
    }

    public function testCreate(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $data = [
            'id' => 'test-id',
            'type' => 'product',
            'referencedId' => 'test-referenced-id',
            'quantity' => 5,
            'payload' => ['foo' => 'test-payload'],
            'removable' => true,
            'stackable' => true,
            'label' => 'test-label',
            'description' => 'test-description',
        ];

        $factory = new ProductLineItemFactory($this->createMock(PriceDefinitionFactory::class));

        $lineItem = $factory->create($data, $context);

        static::assertSame('test-id', $lineItem->getId());
        static::assertSame('product', $lineItem->getType());
        static::assertSame('test-referenced-id', $lineItem->getReferencedId());
        static::assertSame(5, $lineItem->getQuantity());
        static::assertSame(['foo' => 'test-payload'], $lineItem->getPayload());
        static::assertTrue($lineItem->isRemovable());
        static::assertTrue($lineItem->isStackable());
        static::assertTrue($lineItem->isModified());
    }

    public function testCreateWithPriceDefinition(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $data = [
            'id' => 'test-id',
            'type' => 'product',
            'priceDefinition' => [
                'type' => 'test-type',
                'price' => 100,
                'precision' => 2,
            ],
        ];

        $priceDefinition = new AbsolutePriceDefinition(100.0);

        $priceDefinitionFactory = $this->createMock(PriceDefinitionFactory::class);
        $priceDefinitionFactory
            ->expects(static::once())
            ->method('factory')
            ->with(
                static::equalTo($context->getContext()),
                static::equalTo($data['priceDefinition']),
                static::equalTo('product')
            )
            ->willReturn($priceDefinition);

        $factory = new ProductLineItemFactory($priceDefinitionFactory);

        $lineItem = $factory->create($data, $context);

        static::assertSame('test-id', $lineItem->getId());
        static::assertSame('product', $lineItem->getType());
        static::assertSame('test-id', $lineItem->getReferencedId());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertSame($priceDefinition, $lineItem->getPriceDefinition());
        static::assertTrue($lineItem->isModified());

        static::assertTrue($lineItem->hasExtension(ProductCartProcessor::CUSTOM_PRICE));

        $extension = $lineItem->getExtension(ProductCartProcessor::CUSTOM_PRICE);

        static::assertEquals(new ArrayEntity(), $extension);
    }

    public function testCreateWithoutPermission(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => false]);

        $data = ['id' => 'test-id', 'priceDefinition' => 'foo'];

        $factory = new ProductLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
        );

        $this->expectException(CartException::class);

        $factory->create($data, $context);
    }

    public function testUpdateWithoutPermissions(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => false]);

        $factory = new ProductLineItemFactory($this->createMock(PriceDefinitionFactory::class));

        $lineItem = new LineItem('test-id', 'product', null, 1);

        $this->expectException(CartException::class);

        $factory->update($lineItem, ['priceDefinition' => 'foo'], $context);
    }
}
