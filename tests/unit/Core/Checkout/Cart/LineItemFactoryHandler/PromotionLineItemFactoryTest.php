<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItemFactoryHandler;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\PromotionLineItemFactory;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PromotionLineItemFactory::class)]
#[Package('checkout')]
class PromotionLineItemFactoryTest extends TestCase
{
    public function testSupports(): void
    {
        $factory = new PromotionLineItemFactory();

        static::assertTrue($factory->supports('promotion'));
        static::assertFalse($factory->supports('credit'));
        static::assertFalse($factory->supports('custom'));
        static::assertFalse($factory->supports('product'));
        static::assertFalse($factory->supports('discount'));
        static::assertFalse($factory->supports('container'));
        static::assertFalse($factory->supports('foo'));
    }

    public function testCreate(): void
    {
        $factory = new PromotionLineItemFactory();

        $data = [
            'id' => 'test-id',
            'referencedId' => 'test-referenced-id',
        ];

        $context = Generator::createSalesChannelContext();

        $lineItem = $factory->create($data, $context);

        static::assertSame(Uuid::fromStringToHex('promotion-test-referenced-id'), $lineItem->getId());
        static::assertSame('test-referenced-id', $lineItem->getReferencedId());
        static::assertFalse($lineItem->isGood());
        static::assertSame(1, $lineItem->getQuantity());
        static::assertSame('promotion', $lineItem->getType());

        $percentagePrice = $lineItem->getPriceDefinition();

        static::assertInstanceOf(PercentagePriceDefinition::class, $percentagePrice);
        static::assertSame(0.0, $percentagePrice->getPercentage());
    }

    public function testUpdate(): void
    {
        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Line item type "promotion" cannot be updated.');

        $factory = new PromotionLineItemFactory();

        $context = Generator::createSalesChannelContext();

        $lineItem = new LineItem('hatoken', 'promotion');

        $factory->update($lineItem, [], $context);
    }
}
