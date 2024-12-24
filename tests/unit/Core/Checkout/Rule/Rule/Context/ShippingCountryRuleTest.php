<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Context;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\Rule\ShippingCountryRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ShippingCountryRule::class)]
class ShippingCountryRuleTest extends TestCase
{
    public function testEquals(): void
    {
        $rule = (new ShippingCountryRule())->assign(['countryIds' => ['SWAG-AREA-COUNTRY-ID-1'], 'operator' => ShippingCountryRule::OPERATOR_EQ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-1');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotEquals(): void
    {
        $rule = (new ShippingCountryRule())->assign(['countryIds' => ['SWAG-AREA-COUNTRY-ID-1'], 'operator' => ShippingCountryRule::OPERATOR_NEQ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-1');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testEqualsWithMultipleCountries(): void
    {
        $rule = (new ShippingCountryRule())->assign(['countryIds' => ['SWAG-AREA-COUNTRY-ID-1', 'SWAG-AREA-COUNTRY-ID-2', 'SWAG-AREA-COUNTRY-ID-3'], 'operator' => ShippingCountryRule::OPERATOR_EQ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-2');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotEqualsWithMultipleCountries(): void
    {
        $rule = (new ShippingCountryRule())->assign(['countryIds' => ['SWAG-AREA-COUNTRY-ID-1', 'SWAG-AREA-COUNTRY-ID-2', 'SWAG-AREA-COUNTRY-ID-3'], 'operator' => ShippingCountryRule::OPERATOR_NEQ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-2');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    #[DataProvider('unsupportedOperators')]
    public function testUnsupportedOperators(string $operator): void
    {
        $rule = (new ShippingCountryRule())
            ->assign([
                'countryIds' => ['SWAG-AREA-COUNTRY-ID-1', 'SWAG-AREA-COUNTRY-ID-2', 'SWAG-AREA-COUNTRY-ID-3'],
                'operator' => $operator,
            ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-2');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        $this->expectException(UnsupportedOperatorException::class);
        $rule->match(new CartRuleScope($cart, $context));
    }

    public function testUnsupportedOperatorMessage(): void
    {
        $rule = (new ShippingCountryRule())
            ->assign([
                'countryIds' => ['SWAG-AREA-COUNTRY-ID-1', 'SWAG-AREA-COUNTRY-ID-2', 'SWAG-AREA-COUNTRY-ID-3'],
                'operator' => ShippingCountryRule::OPERATOR_GTE,
            ]);

        $cart = new Cart('test');

        $context = $this->createMock(SalesChannelContext::class);

        $country = new CountryEntity();
        $country->setId('SWAG-AREA-COUNTRY-ID-2');

        $context
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromCountry($country));

        try {
            $rule->match(new CartRuleScope($cart, $context));
        } catch (UnsupportedOperatorException $e) {
            static::assertSame(ShippingCountryRule::OPERATOR_GTE, $e->getOperator());
            static::assertSame(RuleComparison::class, $e->getClass());
        }
    }

    /**
     * @return array<array{0: string}>
     */
    public static function unsupportedOperators(): array
    {
        return [
            [''],
            [ShippingCountryRule::OPERATOR_GTE],
            [ShippingCountryRule::OPERATOR_LTE],
        ];
    }
}
