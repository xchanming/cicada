<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Rule\CartHasDeliveryFreeItemRule;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(CartHasDeliveryFreeItemRule::class)]
class CartHasDeliveryFreeItemRuleTest extends TestCase
{
    #[DataProvider('inputProvider')]
    public function testMatchInLineItemScope(?bool $lineItemWithFreeDelivery): void
    {
        $scope = new LineItemScope($this->getLineItem($lineItemWithFreeDelivery), $this->createMock(SalesChannelContext::class));

        $rule = new CartHasDeliveryFreeItemRule(true);
        static::assertSame($lineItemWithFreeDelivery ?? false, $rule->match($scope));
        $rule = new CartHasDeliveryFreeItemRule(false);
        static::assertSame(!$lineItemWithFreeDelivery, $rule->match($scope));
    }

    #[DataProvider('inputProvider')]
    public function testMatchInCartScope(?bool $lineItemWithFreeDelivery): void
    {
        $scope = new CartRuleScope(new Cart(Uuid::randomHex()), $this->createMock(SalesChannelContext::class));
        $scope->getCart()->addLineItems(new LineItemCollection([$this->getLineItem($lineItemWithFreeDelivery)]));

        $rule = new CartHasDeliveryFreeItemRule(true);
        static::assertSame($lineItemWithFreeDelivery ?? false, $rule->match($scope));
        $rule = new CartHasDeliveryFreeItemRule(false);
        static::assertSame(!$lineItemWithFreeDelivery, $rule->match($scope));
    }

    public function testMatchInCartScopeWithEmptyCart(): void
    {
        $scope = new CartRuleScope(new Cart(Uuid::randomHex()), $this->createMock(SalesChannelContext::class));

        $rule = new CartHasDeliveryFreeItemRule(true);
        static::assertFalse($rule->match($scope));
        $rule = new CartHasDeliveryFreeItemRule(false);
        static::assertTrue($rule->match($scope));
    }

    public function testMatchInIncompatibleScope(): void
    {
        $scope = new CheckoutRuleScope($this->createMock(SalesChannelContext::class));

        $rule = new CartHasDeliveryFreeItemRule(true);
        static::assertFalse($rule->match($scope));
        $rule = new CartHasDeliveryFreeItemRule(false);
        static::assertFalse($rule->match($scope));
    }

    public function testConstraints(): void
    {
        $rule = new CartHasDeliveryFreeItemRule();
        static::assertCount(1, $rule->getConstraints());
        static::assertArrayHasKey('allowed', $rule->getConstraints());

        static::assertCount(1, $rule->getConstraints()['allowed']);

        $constraint = \current($rule->getConstraints()['allowed']);
        static::assertInstanceOf(Type::class, $constraint);
        static::assertSame('bool', $constraint->type);
    }

    public function testRuleConfig(): void
    {
        $rule = new CartHasDeliveryFreeItemRule();

        $fields = $rule->getConfig()->getData()['fields'];

        static::assertCount(1, $fields);
        static::assertContains([
            'name' => 'allowed',
            'type' => 'bool',
            'config' => [],
        ], $fields);
    }

    public function testName(): void
    {
        $rule = new CartHasDeliveryFreeItemRule();
        static::assertSame('cartHasDeliveryFreeItem', $rule->getName());
    }

    /**
     * @return array<string, array<bool|null>>
     */
    public static function inputProvider(): array
    {
        return [
            'free item' => [true],
            'not free item' => [false],
            'no delivery information' => [null],
        ];
    }

    private function getLineItem(?bool $freeDelivery = null): LineItem
    {
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($freeDelivery !== null) {
            $lineItem->setDeliveryInformation(new DeliveryInformation(3, null, $freeDelivery));
        }

        return $lineItem;
    }
}
