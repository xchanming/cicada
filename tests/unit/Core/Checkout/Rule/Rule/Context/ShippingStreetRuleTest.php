<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Context;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Rule\ShippingStreetRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ShippingStreetRule::class)]
class ShippingStreetRuleTest extends TestCase
{
    public function testWithExactMatch(): void
    {
        $rule = (new ShippingStreetRule())->assign(['streetName' => 'example street']);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getShippingLocation')
            ->willReturn(
                ShippingLocation::createFromAddress(
                    $this->createAddress('example street')
                )
            );

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testCaseInsensitive(): void
    {
        $rule = (new ShippingStreetRule())->assign(['streetName' => 'ExaMple StreEt']);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getShippingLocation')
            ->willReturn(
                ShippingLocation::createFromAddress(
                    $this->createAddress('example street')
                )
            );

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotMatch(): void
    {
        $rule = (new ShippingStreetRule())->assign(['streetName' => 'example street']);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getShippingLocation')
            ->willReturn(
                ShippingLocation::createFromAddress(
                    $this->createAddress('test street')
                )
            );

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testWithoutAddress(): void
    {
        $rule = (new ShippingStreetRule())->assign(['streetName' => 'ExaMple StreEt']);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getShippingLocation')
            ->willReturn(
                ShippingLocation::createFromCountry(
                    new CountryEntity()
                )
            );

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    private function createAddress(string $street): CustomerAddressEntity
    {
        $address = new CustomerAddressEntity();
        $state = new CountryStateEntity();
        $country = new CountryEntity();
        $state->setCountryId('SWAG-AREA-COUNTRY-ID-1');

        $address->setStreet($street);
        $address->setCountry($country);
        $address->setCountryState($state);

        return $address;
    }
}
