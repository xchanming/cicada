<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Delivery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Cicada\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\DeliveryTime\DeliveryTimeEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(DeliveryCalculator::class)]
class DeliveryCalculatorTest extends TestCase
{
    private DeliveryTime $deliveryTime;

    protected function setUp(): void
    {
        $this->deliveryTime = (new DeliveryTime())->assign([
            'min' => 1,
            'max' => 3,
            'unit' => 'day',
            'name' => '1-3 days',
        ]);
        $deliveryTimeEntity = new DeliveryTimeEntity();
        $deliveryTimeEntity->assign([
            'min' => 1,
            'max' => 3,
            'unit' => 'day',
            'name' => '1-3 days',
        ]);
    }

    public function testCalculateAdminShippingCostZero(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getItemRounding')->willReturn(new CashRoundingConfig(2, 0.01, true));

        $delivery = $this->getMockBuilder(Delivery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $costs = new CalculatedPrice(0.0, 0.0, new CalculatedTaxCollection(), new TaxRuleCollection());
        $delivery->expects(static::atLeastOnce())->method('getShippingCosts')->willReturn($costs);
        $newCosts = null;
        $delivery->expects(static::once())->method('setShippingCosts')->willReturnCallback(function ($costsParameter) use (&$newCosts): void {
            /** @var CalculatedPrice $newCosts */
            $newCosts = $costsParameter;
        });

        $lineItem = new LineItem(Uuid::randomHex(), 'product');
        $lineItem->setDeliveryInformation(
            new DeliveryInformation(
                10,
                12.0,
                true,
                null,
                $this->deliveryTime
            )
        );
        $lineItem->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $price = $lineItem->getPrice();
        static::assertNotNull($price);

        $delivery->expects(static::once())->method('getPositions')->willReturn(
            new DeliveryPositionCollection(
                [
                    new DeliveryPosition(
                        Uuid::randomHex(),
                        $lineItem,
                        1,
                        $price,
                        new DeliveryDate(new \DateTime(), new \DateTime())
                    ),
                ]
            )
        );

        $data = new CartDataCollection();

        $cart = new Cart('test');
        $cartBehavior = new CartBehavior([
            DeliveryProcessor::SKIP_DELIVERY_PRICE_RECALCULATION => true,
        ]);
        $cart->setBehavior($cartBehavior);

        $quantityPriceCalculatorMock = $this->createMock(QuantityPriceCalculator::class);
        $quantityPriceCalculatorMock->expects(static::once())->method('calculate')->willReturn($costs);

        $deliveryCalculator = new DeliveryCalculator(
            $quantityPriceCalculatorMock,
            $this->createMock(PercentageTaxRuleBuilder::class),
        );

        $deliveryCalculator->calculate($data, $cart, new DeliveryCollection([$delivery]), $context);
        static::assertNotNull($newCosts);
        static::assertSame($costs->getUnitPrice(), $newCosts->getUnitPrice());
        static::assertSame($costs->getTotalPrice(), $newCosts->getTotalPrice());
    }
}
