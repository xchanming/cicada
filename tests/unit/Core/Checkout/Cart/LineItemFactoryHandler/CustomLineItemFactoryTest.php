<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItemFactoryHandler;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\CustomLineItemFactory;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Product\Cart\ProductCartProcessor;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomLineItemFactory::class)]
#[Package('checkout')]
class CustomLineItemFactoryTest extends TestCase
{
    public function testSupports(): void
    {
        $factory = new CustomLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
            $this->createMock(EntityRepository::class)
        );

        static::assertTrue($factory->supports('custom'));
        static::assertFalse($factory->supports('credit'));
        static::assertFalse($factory->supports('product'));
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
            'type' => 'custom',
            'referencedId' => 'test-referenced-id',
            'quantity' => 5,
            'payload' => ['foo' => 'test-payload'],
            'removable' => true,
            'stackable' => true,
            'label' => 'test-label',
            'description' => 'test-description',
        ];

        $factory = new CustomLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
            $this->createMock(EntityRepository::class)
        );

        $lineItem = $factory->create($data, $context);

        static::assertSame('test-id', $lineItem->getId());
        static::assertSame('custom', $lineItem->getType());
        static::assertSame('test-referenced-id', $lineItem->getReferencedId());
        static::assertSame(5, $lineItem->getQuantity());
        static::assertSame(['foo' => 'test-payload'], $lineItem->getPayload());
        static::assertTrue($lineItem->isRemovable());
        static::assertTrue($lineItem->isStackable());
        static::assertSame('test-label', $lineItem->getLabel());
        static::assertSame('test-description', $lineItem->getDescription());
        static::assertTrue($lineItem->isModified());
    }

    public function testCreateWithCoverId(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $data = [
            'id' => 'test-id',
            'type' => 'custom',
            'coverId' => 'test-cover-id',
        ];

        $expectedCriteria = new Criteria(['test-cover-id']);
        $mediaEntity = new MediaEntity();
        $mediaEntity->setId('test-cover-id');

        $result = new EntitySearchResult(
            'media',
            1,
            new EntityCollection([$mediaEntity]),
            null,
            $expectedCriteria,
            $context->getContext()
        );

        $mediaRepo = $this->createMock(EntityRepository::class);
        $mediaRepo
            ->expects(static::once())
            ->method('search')
            ->with(static::equalTo($expectedCriteria), $context->getContext())
            ->willReturn($result);

        $factory = new CustomLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
            $mediaRepo
        );

        $lineItem = $factory->create($data, $context);

        static::assertSame('test-id', $lineItem->getId());
        static::assertSame('custom', $lineItem->getType());
        static::assertNull($lineItem->getReferencedId());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertSame($mediaEntity, $lineItem->getCover());
        static::assertTrue($lineItem->isModified());
    }

    public function testCreateWithPriceDefinition(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]);

        $data = [
            'id' => 'test-id',
            'type' => 'custom',
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
                static::equalTo('custom')
            )
            ->willReturn($priceDefinition);

        $factory = new CustomLineItemFactory(
            $priceDefinitionFactory,
            $this->createMock(EntityRepository::class)
        );

        $lineItem = $factory->create($data, $context);

        static::assertSame('test-id', $lineItem->getId());
        static::assertSame('custom', $lineItem->getType());
        static::assertNull($lineItem->getReferencedId());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertSame($priceDefinition, $lineItem->getPriceDefinition());
        static::assertTrue($lineItem->isModified());
    }

    public function testCreateWithoutPermission(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => false]);

        $data = [
            'id' => 'test-id',
            'type' => 'custom',
        ];

        $factory = new CustomLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
            $this->createMock(EntityRepository::class)
        );

        $this->expectException(CartException::class);

        $factory->create($data, $context);
    }

    public function testUpdateWithoutPermissions(): void
    {
        $context = Generator::generateSalesChannelContext();
        $context->setPermissions([ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => false]);

        $factory = new CustomLineItemFactory(
            $this->createMock(PriceDefinitionFactory::class),
            $this->createMock(EntityRepository::class)
        );

        $lineItem = new LineItem('test-id', 'custom', null, 1);

        $this->expectException(CartException::class);

        $factory->update($lineItem, [], $context);
    }
}
