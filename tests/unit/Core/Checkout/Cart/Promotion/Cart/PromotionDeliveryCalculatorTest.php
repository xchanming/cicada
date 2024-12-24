<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Cicada\Core\Checkout\Promotion\Cart\Error\PromotionNotEligibleError;
use Cicada\Core\Checkout\Promotion\Cart\PromotionDeliveryCalculator;
use Cicada\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\Stub\Rule\FalseRule;

/**
 * @internal
 */
#[CoversClass(PromotionDeliveryCalculator::class)]
class PromotionDeliveryCalculatorTest extends TestCase
{
    private IdsCollection $ids;

    private PromotionDeliveryCalculator $promotionDeliveryCalculator;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->promotionDeliveryCalculator = new PromotionDeliveryCalculator(
            $this->createMock(QuantityPriceCalculator::class),
            $this->createMock(PercentagePriceCalculator::class),
            $this->createMock(PromotionItemBuilder::class)
        );
    }

    public function testPromotionPrioritySorting(): void
    {
        $lineItems = new LineItem($this->ids->get('line-item-1'), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItems->setPriceDefinition(new AbsolutePriceDefinition(50.0));
        $lineItems->setLabel('Product');

        $firstDiscountItem = $this->getDiscountItem('first-promotion')
            ->setPayloadValue('code', 'code-1')
            ->setPayloadValue('exclusions', ['second-promotion'])
            ->setPayloadValue('priority', 2)
            ->setRequirement(new AndRule([new FalseRule()]));

        $secondDiscountItem = $this->getDiscountItem('second-promotion')
            ->setPayloadValue('code', 'code-2')
            ->setPayloadValue('exclusions', ['first-promotion'])
            ->setPayloadValue('priority', 1)
            ->setPriceDefinition(new AbsolutePriceDefinition(-20.0));
        $delivery = new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTimeImmutable(), new \DateTimeImmutable()),
            new ShippingMethodEntity(),
            new ShippingLocation(new CountryEntity(), null, null),
            new CalculatedPrice(1.0, 1.0, new CalculatedTaxCollection(), new TaxRuleCollection())
        );

        $cart = new Cart('promotion-test');
        $cart->addLineItems(new LineItemCollection([$lineItems]));
        $cart->setDeliveries(new DeliveryCollection([$delivery]));

        $this->promotionDeliveryCalculator->calculate(
            new LineItemCollection([$secondDiscountItem, $firstDiscountItem]),
            $cart,
            $cart,
            $this->createMock(SalesChannelContext::class)
        );

        static::assertCount(2, $cart->getLineItems());

        $error = $cart->getErrors()->first();
        static::assertInstanceOf(PromotionNotEligibleError::class, $error);
        static::assertEquals('Promotion first-promotion not eligible for cart!', $error->getMessage());
    }

    private function getDiscountItem(string $promotionId): LineItem
    {
        $discountItemToBeExcluded = new LineItem($promotionId, PromotionProcessor::LINE_ITEM_TYPE);
        $discountItemToBeExcluded->setRequirement(null);
        $discountItemToBeExcluded->setPayloadValue('discountScope', PromotionDiscountEntity::SCOPE_DELIVERY);
        $discountItemToBeExcluded->setPayloadValue('discountType', PromotionDiscountEntity::TYPE_ABSOLUTE);
        $discountItemToBeExcluded->setPayloadValue('exclusions', []);
        $discountItemToBeExcluded->setPayloadValue('promotionId', $promotionId);
        $discountItemToBeExcluded->setReferencedId($promotionId);
        $discountItemToBeExcluded->setLabel($promotionId);
        $discountItemToBeExcluded->setPriceDefinition(new AbsolutePriceDefinition(-10.0));

        return $discountItemToBeExcluded;
    }
}
