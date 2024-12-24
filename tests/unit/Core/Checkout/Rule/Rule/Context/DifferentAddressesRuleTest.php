<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Context;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\DifferentAddressesRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(DifferentAddressesRule::class)]
class DifferentAddressesRuleTest extends TestCase
{
    public function testRuleMatch(): void
    {
        $rule = new DifferentAddressesRule();

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $billing = new CustomerAddressEntity();
        $billing->setId('SWAG-CUSTOMER-ADDRESS-ID-1');

        $shipping = new CustomerAddressEntity();
        $shipping->setId('SWAG-CUSTOMER-ADDRESS-ID-2');

        $customer = new CustomerEntity();
        $customer->setDefaultBillingAddress($billing);
        $customer->setDefaultShippingAddress($shipping);

        $context
            ->method('getCustomer')
            ->willReturn($customer);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleNotMatch(): void
    {
        $rule = new DifferentAddressesRule();

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $billing = new CustomerAddressEntity();
        $billing->setId('SWAG-CUSTOMER-ADDRESS-ID-1');

        $shipping = new CustomerAddressEntity();
        $shipping->setId('SWAG-CUSTOMER-ADDRESS-ID-1');

        $customer = new CustomerEntity();
        $customer->setDefaultBillingAddress($billing);
        $customer->setDefaultShippingAddress($shipping);

        $context
            ->method('getCustomer')
            ->willReturn($customer);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithoutCustomer(): void
    {
        $rule = new DifferentAddressesRule();

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getCustomer')
            ->willReturn(null);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
