<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Cart\Discount;

use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiscountLineItem::class)]
class DiscountLineItemTest extends TestCase
{
    private DiscountLineItem $discount;

    protected function setUp(): void
    {
        $this->discount = new DiscountLineItem(
            'Black Friday',
            new QuantityPriceDefinition(29, new TaxRuleCollection()),
            [
                'discountScope' => 'cart',
                'discountType' => 'absolute',
                'filter' => [
                    'sorterKey' => 'PRICE_ASC',
                    'applierKey' => 'ALL',
                    'usageKey' => 'UNLIMITED',
                ],
            ],
            'bf'
        );
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testLabel(): void
    {
        static::assertEquals('Black Friday', $this->discount->getLabel());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testScope(): void
    {
        static::assertEquals('cart', $this->discount->getScope());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testType(): void
    {
        static::assertEquals('absolute', $this->discount->getType());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testCode(): void
    {
        static::assertEquals('bf', $this->discount->getCode());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testPriceDefinition(): void
    {
        static::assertInstanceOf(QuantityPriceDefinition::class, $this->discount->getPriceDefinition());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testSorterApplierKey(): void
    {
        static::assertEquals('PRICE_ASC', $this->discount->getFilterSorterKey());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testFilterApplierKey(): void
    {
        static::assertEquals('ALL', $this->discount->getFilterApplierKey());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testUsageApplierKey(): void
    {
        static::assertEquals('UNLIMITED', $this->discount->getFilterUsageKey());
    }

    /**
     * This test verifies that the property is correctly
     * assigned as well as returned in the getter function.
     */
    #[Group('promotions')]
    public function testPayloads(): void
    {
        $expected = [
            'discountScope' => 'cart',
            'discountType' => 'absolute',
            'filter' => [
                'sorterKey' => 'PRICE_ASC',
                'applierKey' => 'ALL',
                'usageKey' => 'UNLIMITED',
            ],
        ];

        static::assertEquals($expected, $this->discount->getPayload());
    }
}
