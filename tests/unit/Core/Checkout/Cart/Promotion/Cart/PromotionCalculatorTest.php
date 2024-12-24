<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\AmountCalculator;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Composition\DiscountCompositionBuilder;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackager;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\AdvancedPackagePicker;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\PackageFilter;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\SetGroupScopeFilter;
use Cicada\Core\Checkout\Promotion\Cart\Error\PromotionExcludedError;
use Cicada\Core\Checkout\Promotion\Cart\PromotionCalculator;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[CoversClass(PromotionCalculator::class)]
class PromotionCalculatorTest extends TestCase
{
    private IdsCollection $ids;

    private PromotionCalculator $promotionCalculator;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->promotionCalculator = new PromotionCalculator(
            $this->createMock(AmountCalculator::class),
            $this->createMock(AbsolutePriceCalculator::class),
            $this->createMock(LineItemGroupBuilder::class),
            $this->createMock(DiscountCompositionBuilder::class),
            $this->createMock(PackageFilter::class),
            $this->createMock(AdvancedPackagePicker::class),
            $this->createMock(SetGroupScopeFilter::class),
            $this->createMock(LineItemQuantitySplitter::class),
            $this->createMock(PercentagePriceCalculator::class),
            $this->createMock(DiscountPackager::class),
            $this->createMock(DiscountPackager::class),
            $this->createMock(DiscountPackager::class)
        );
    }

    public function testPromotionPrioritySorting(): void
    {
        $lineItems = new LineItem($this->ids->get('line-item-1'), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItems->setPriceDefinition(new AbsolutePriceDefinition(50.0));
        $lineItems->setLabel('Product');

        $firstDiscountItem = $this->getDiscountItem('frist-promotion')
            ->setPayloadValue('code', 'code-1')
            ->setPayloadValue('exclusions', ['second-promotion'])
            ->setPayloadValue('priority', 2);

        $secondDiscountItem = $this->getDiscountItem('second-promotion')
            ->setPayloadValue('code', 'code-2')
            ->setPayloadValue('exclusions', ['frist-promotion'])
            ->setPayloadValue('priority', 1)
            ->setPriceDefinition(new AbsolutePriceDefinition(-20.0));

        $cart = new Cart('promotion-test');
        $cart->addLineItems(new LineItemCollection([$lineItems]));

        $this->promotionCalculator->calculate(
            new LineItemCollection([$secondDiscountItem, $firstDiscountItem]),
            $cart,
            $cart,
            $this->createMock(SalesChannelContext::class),
            new CartBehavior()
        );

        static::assertCount(1, $cart->getErrors());
        $error = $cart->getErrors()->first();

        static::assertInstanceOf(PromotionExcludedError::class, $error);
        static::assertEquals('Promotion second-promotion was excluded for cart.', $error->getMessage());
    }

    private function getDiscountItem(string $promotionId): LineItem
    {
        $discountItemToBeExcluded = new LineItem($promotionId, PromotionProcessor::LINE_ITEM_TYPE);
        $discountItemToBeExcluded->setRequirement(null);
        $discountItemToBeExcluded->setPayloadValue('discountScope', PromotionDiscountEntity::SCOPE_CART);
        $discountItemToBeExcluded->setPayloadValue('discountType', PromotionDiscountEntity::TYPE_ABSOLUTE);
        $discountItemToBeExcluded->setPayloadValue('exclusions', []);
        $discountItemToBeExcluded->setPayloadValue('promotionId', $promotionId);
        $discountItemToBeExcluded->setReferencedId($promotionId);
        $discountItemToBeExcluded->setLabel('Discount');
        $discountItemToBeExcluded->setPriceDefinition(new AbsolutePriceDefinition(-10.0));

        return $discountItemToBeExcluded;
    }
}
