<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Rule\ShippingZipCodeRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ShippingZipCodeRule::class)]
class ShippingZipCodeRuleTest extends TestCase
{
    public function testEqualsWithSingleCode(): void
    {
        $rule = (new ShippingZipCodeRule())->assign(['zipCodes' => ['ABC123']]);
        $address = $this->createAddress('ABC123');

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $location = ShippingLocation::createFromAddress($address);

        $context
            ->method('getShippingLocation')
            ->willReturn($location);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testEqualsWithMultipleCodes(): void
    {
        $rule = (new ShippingZipCodeRule())->assign(['zipCodes' => ['ABC1', 'ABC2', 'ABC3']]);
        $address = $this->createAddress('ABC2');

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $location = ShippingLocation::createFromAddress($address);

        $context
            ->method('getShippingLocation')
            ->willReturn($location);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotMatchWithSingleCode(): void
    {
        $rule = (new ShippingZipCodeRule())->assign(['zipCodes' => ['ABC1', 'ABC2', 'ABC3']]);
        $address = $this->createAddress('ABC4');

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $location = ShippingLocation::createFromAddress($address);

        $context
            ->method('getShippingLocation')
            ->willReturn($location);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testWithoutShippingAddress(): void
    {
        $rule = (new ShippingZipCodeRule())->assign(['zipCodes' => ['ABC1', 'ABC2', 'ABC3']]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $location = ShippingLocation::createFromCountry(new CountryEntity());

        $context
            ->method('getShippingLocation')
            ->willReturn($location);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    private function createAddress(string $code): CustomerAddressEntity
    {
        $address = new CustomerAddressEntity();
        $address->setZipcode($code);
        $address->setCountry(new CountryEntity());

        return $address;
    }
}
