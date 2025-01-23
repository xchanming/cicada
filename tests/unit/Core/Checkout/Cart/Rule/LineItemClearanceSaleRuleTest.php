<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemClearanceSaleRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(LineItemClearanceSaleRule::class)]
#[Group('rules')]
class LineItemClearanceSaleRuleTest extends TestCase
{
    use CartRuleHelperTrait;

    private LineItemClearanceSaleRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LineItemClearanceSaleRule();
    }

    public function testGetName(): void
    {
        static::assertSame('cartLineItemClearanceSale', $this->rule->getName());
    }

    public function testGetConstraints(): void
    {
        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('clearanceSale', $ruleConstraints, 'Rule Constraint clearanceSale is not defined');
    }

    #[DataProvider('getLineItemScopeTestData')]
    public function testIfMatchesCorrectWithLineItemScope(bool $ruleActive, bool $clearanceSale, bool $expected): void
    {
        $this->rule->assign(['clearanceSale' => $ruleActive]);

        $match = $this->rule->match(new LineItemScope(
            $this->createLineItemWithClearance($clearanceSale),
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function getLineItemScopeTestData(): array
    {
        return [
            'rule yes / clearance sale yes' => [true, true, true],
            'rule yes / clearance sale no' => [true, false, false],
            'rule no / clearance sale yes' => [false, true, false],
            'rule no / clearance sale no' => [false, false, true],
        ];
    }

    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScope(bool $ruleActive, bool $clearanceSale, bool $expected): void
    {
        $this->rule->assign(['clearanceSale' => $ruleActive]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithClearance($clearanceSale),
            $this->createLineItemWithClearance(false),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScopeNested(bool $ruleActive, bool $clearanceSale, bool $expected): void
    {
        $this->rule->assign(['clearanceSale' => $ruleActive]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithClearance($clearanceSale),
            $this->createLineItemWithClearance(false),
        ]);

        $containerLineItem = $this->createContainerLineItem($lineItemCollection);
        $cart = $this->createCart(new LineItemCollection([$containerLineItem]));

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function getCartRuleScopeTestData(): array
    {
        return [
            'rule yes / clearance sale yes' => [true, true, true],
            'rule yes / clearance sale no' => [true, false, false],
            'rule no / clearance sale no' => [false, false, true],
            'rule no / clearance sale yes' => [false, true, true],
        ];
    }

    public function testMatchWithWrongScopeShouldReturnFalse(): void
    {
        $goodsCountRule = new LineItemClearanceSaleRule();
        $wrongScope = $this->createMock(RuleScope::class);

        static::assertFalse($goodsCountRule->match($wrongScope));
    }

    public function testGetConfig(): void
    {
        $cartVolumeRule = new LineItemClearanceSaleRule();

        $result = $cartVolumeRule->getConfig()->getData();

        static::assertSame('clearanceSale', $result['fields']['clearanceSale']['name']);
    }

    private function createLineItemWithClearance(bool $clearanceSaleEnabled): LineItem
    {
        return $this->createLineItem()->setPayloadValue('isCloseout', $clearanceSaleEnabled);
    }
}
