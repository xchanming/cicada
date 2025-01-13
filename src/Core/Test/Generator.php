<?php declare(strict_types=1);

namespace Cicada\Core\Test;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\SalesChannel\Context\LanguageInfo;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class Generator extends TestCase
{
    final public const TOKEN = 'test-token';
    final public const DOMAIN = 'test-domain';
    final public const NAVIGATION_CATEGORY = 'f8466865cc6a45e48ed98dd2f6a0a293';
    final public const TAX_CALCULATION_TYPE = SalesChannelDefinition::CALCULATION_TYPE_HORIZONTAL;
    final public const CUSTOMER_GROUP_DISPLAY_GROSS = true;
    final public const TAX = 'c725e107825c4c7281673aeea66ed67e';
    final public const TAX_RATE = 19.0;
    final public const PAYMENT_METHOD = 'cce0e1ca23de4c55868ce057f628c349';
    final public const SHIPPING_METHOD = '37dbe80c5cbb4852a97cb742ed04ba41';
    final public const COUNTRY = 'd4eb3205dd9444169b3f60c056c313a1';
    final public const COUNTRY_STATE = '119d6e30fc4f468daa88ff5b413e9322';
    final public const CUSTOMER_ADDRESS = '08f1594313494c3e9eb57bb53486fe61';
    final public const CUSTOMER = '42d58aa78cf14851968a786a66bab93a';
    final public const LANGUAGE_INFO_NAME = 'English';
    final public const LANGUAGE_INFO_LOCALE_CODE = 'en-GB';

    /**
     * @param array<string, string[]> $areaRuleIds
     * @param array<array-key, mixed> $overrides
     */
    public static function generateSalesChannelContext(
        ?Context $baseContext = null,
        ?string $token = null,
        ?string $domainId = null,
        ?SalesChannelEntity $salesChannel = null,
        ?CurrencyEntity $currency = null,
        ?CustomerGroupEntity $currentCustomerGroup = null,
        ?TaxCollection $taxRules = null,
        ?PaymentMethodEntity $paymentMethod = null,
        ?ShippingMethodEntity $shippingMethod = null,
        ?ShippingLocation $shippingLocation = null,
        ?CustomerEntity $customer = null,
        ?CashRoundingConfig $itemRounding = null,
        ?CashRoundingConfig $totalRounding = null,
        ?array $areaRuleIds = [],
        ?LanguageInfo $languageInfo = null,
        ?CountryEntity $country = null,
        ?CountryStateEntity $countryState = null,
        ?CustomerAddressEntity $customerAddress = null,
        ?array $overrides = [],
    ): SalesChannelContext {
        $baseContext ??= Context::createDefaultContext();

        $token ??= self::TOKEN;

        $domainId ??= self::DOMAIN;

        if (!$salesChannel) {
            $salesChannel = new SalesChannelEntity();
            $salesChannel->setId(TestDefaults::SALES_CHANNEL);
            $salesChannel->setNavigationCategoryId(self::NAVIGATION_CATEGORY);
            $salesChannel->setTaxCalculationType(self::TAX_CALCULATION_TYPE);
        }

        if (!$currency) {
            $currency = new CurrencyEntity();
            $currency->setId($baseContext->getCurrencyId());
            $currency->setFactor($baseContext->getCurrencyFactor());
        }

        if (!$currentCustomerGroup) {
            $currentCustomerGroup = new CustomerGroupEntity();
            $currentCustomerGroup->setId(TestDefaults::FALLBACK_CUSTOMER_GROUP);
            $currentCustomerGroup->setDisplayGross(self::CUSTOMER_GROUP_DISPLAY_GROSS);
        }

        if (!$taxRules) {
            $tax = new TaxEntity();
            $tax->setId(self::TAX);
            $tax->setTaxRate(self::TAX_RATE);

            $taxRules = new TaxCollection([$tax]);
        }

        if (!$paymentMethod) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId(self::PAYMENT_METHOD);
        }

        $salesChannel->setPaymentMethodIds([$paymentMethod->getId()]);
        $salesChannel->setPaymentMethodId($paymentMethod->getId());
        $salesChannel->setPaymentMethod($paymentMethod);

        if (!$shippingMethod) {
            $shippingMethod = new ShippingMethodEntity();
            $shippingMethod->setId(self::SHIPPING_METHOD);
        }

        $salesChannel->setShippingMethodId($shippingMethod->getId());
        $salesChannel->setShippingMethod($shippingMethod);

        if (!$shippingLocation) {
            if (!$country) {
                $country = new CountryEntity();
                $country->setId(self::COUNTRY);
            }

            if (!$countryState) {
                $countryState = new CountryStateEntity();
                $countryState->setId(self::COUNTRY_STATE);
                $countryState->setCountryId($country->getId());
                $countryState->setCountry($country);
            }

            if (!$customerAddress) {
                $customerAddress = new CustomerAddressEntity();
                $customerAddress->setId(self::CUSTOMER_ADDRESS);
            }

            $customerAddress->setCountryId($country->getId());
            $customerAddress->setCountry($country);
            $customerAddress->setCountryStateId($countryState->getId());
            $customerAddress->setCountryState($countryState);

            $shippingLocation = ShippingLocation::createFromAddress($customerAddress);
        }

        if (!$customer) {
            $customer = new CustomerEntity();
            $customer->setId(self::CUSTOMER);
            $customer->setGroupId($currentCustomerGroup->getId());
            $customer->setGroup($currentCustomerGroup);
            $customer->setSalesChannelId($salesChannel->getId());
            $customer->setSalesChannel($salesChannel);
        }

        $itemRounding ??= clone $baseContext->getRounding();

        $totalRounding ??= clone $baseContext->getRounding();

        $areaRuleIds ??= [];

        $languageInfo ??= new LanguageInfo(self::LANGUAGE_INFO_NAME, self::LANGUAGE_INFO_LOCALE_CODE);

        $salesChannelContext = new SalesChannelContext(
            baseContext: $baseContext,
            token: $token,
            domainId: $domainId,
            salesChannel: $salesChannel,
            currency: $currency,
            currentCustomerGroup: $currentCustomerGroup,
            taxRules: $taxRules,
            paymentMethod: $paymentMethod,
            shippingMethod: $shippingMethod,
            shippingLocation: $shippingLocation,
            customer: $customer,
            itemRounding: $itemRounding,
            totalRounding: $totalRounding,
            areaRuleIds: $areaRuleIds,
            languageInfo: $languageInfo,
        );

        if ($overrides) {
            $salesChannelContext->assign($overrides);
        }

        return $salesChannelContext;
    }

    public static function createCart(): Cart
    {
        $cart = new Cart('test');
        $cart->setLineItems(
            new LineItemCollection([
                (new LineItem('A', 'product', 'A', 27))
                    ->setPrice(new CalculatedPrice(10, 270, new CalculatedTaxCollection(), new TaxRuleCollection(), 27)),
                (new LineItem('B', 'test', 'B', 5))
                    ->setGood(false)
                    ->setPrice(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection())),
            ])
        );
        $cart->setPrice(
            new CartPrice(
                275.0,
                275.0,
                0,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                CartPrice::TAX_STATE_GROSS
            )
        );

        return $cart;
    }

    public static function createCartWithDelivery(): Cart
    {
        $cart = static::createCart();

        $shippingMethod = new ShippingMethodEntity();
        $calculatedPrice = new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection());
        $deliveryDate = new DeliveryDate(new \DateTime(), new \DateTime());

        $deliveryPositionCollection = new DeliveryPositionCollection();
        foreach ($cart->getLineItems() as $lineItem) {
            $deliveryPosition = new DeliveryPosition(
                'anyIdentifier',
                $lineItem,
                $lineItem->getQuantity(),
                $calculatedPrice,
                $deliveryDate
            );

            $lineItem->setDeliveryInformation(new DeliveryInformation(1000, 10.0, false, 2, null, 10.0, 10.0, 10.0));

            $deliveryPositionCollection->add($deliveryPosition);
        }

        $delivery = new Delivery(
            $deliveryPositionCollection,
            $deliveryDate,
            $shippingMethod,
            new ShippingLocation(new CountryEntity(), null, null),
            $calculatedPrice
        );

        $cart->addDeliveries(new DeliveryCollection([$delivery]));

        return $cart;
    }
}
