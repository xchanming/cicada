<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Context;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(CustomerGroupRule::class)]
class CustomerGroupRuleTest extends TestCase
{
    public function testMatch(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-1');

        $context = Generator::generateSalesChannelContext(currentCustomerGroup: $group);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testMultipleGroups(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-2', 'SWAG-CUSTOMER-GROUP-ID-3', 'SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-3');

        $context = Generator::generateSalesChannelContext(currentCustomerGroup: $group);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotMatch(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-2', 'SWAG-CUSTOMER-GROUP-ID-3', 'SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-5');

        $context = Generator::generateSalesChannelContext(currentCustomerGroup: $group);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
