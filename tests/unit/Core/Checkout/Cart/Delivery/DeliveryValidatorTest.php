<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Delivery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\DeliveryValidator;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DeliveryValidator::class)]
class DeliveryValidatorTest extends TestCase
{
    private const SHIPPING_METHOD_AVAILABILITY_RULE_ID = 'shipping-method-availability-rule-id';

    public function testValidateDeliveryShallBeValid(): void
    {
        $cart = new Cart('test');
        $context = $this->createMock(SalesChannelContext::class);
        $cart->setDeliveries(new DeliveryCollection([$this->generateDeliveryDummy(self::SHIPPING_METHOD_AVAILABILITY_RULE_ID)]));
        $context->expects(static::once())->method('getRuleIds')->willReturn([self::SHIPPING_METHOD_AVAILABILITY_RULE_ID]);

        $validator = new DeliveryValidator();
        $errors = new ErrorCollection();
        $validator->validate($cart, $errors, $context);

        static::assertCount(0, $errors, 'A delivery without a valid availability rule id should be valid but an error is thrown.');
    }

    public function testValidateDeliveryShippingMethodWithNoAvailabilityRuleShallBeValid(): void
    {
        $cart = new Cart('test');
        $context = $this->createMock(SalesChannelContext::class);
        $cart->setDeliveries(new DeliveryCollection([$this->generateDeliveryDummy(null)]));

        $validator = new DeliveryValidator();
        $errors = new ErrorCollection();
        $validator->validate($cart, $errors, $context);

        static::assertCount(0, $errors, 'A delivery without an availability rule should be valid but an error is thrown.');
    }

    public function testValidateDeliveryShippingMethodAvailabilityRuleIdWithEmptyStringShallThrowAnError(): void
    {
        $cart = new Cart('test');
        $context = $this->createMock(SalesChannelContext::class);
        $cart->setDeliveries(new DeliveryCollection([$this->generateDeliveryDummy('')]));

        $validator = new DeliveryValidator();
        $errors = new ErrorCollection();
        $validator->validate($cart, $errors, $context);

        static::assertCount(1, $errors, 'A delivery with an empty string as availability rule should not be valid but no error is thrown.');
        static::assertSame('Shipping method  not available', $errors->first()?->getMessage());
    }

    private function generateDeliveryDummy(?string $availabilityRuleId): Delivery
    {
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setAvailabilityRuleId($availabilityRuleId);
        $shippingMethod->setActive(true);

        $deliveryDate = new DeliveryDate(new \DateTime(), new \DateTime());

        return new Delivery(
            new DeliveryPositionCollection(),
            $deliveryDate,
            $shippingMethod,
            new ShippingLocation(new CountryEntity(), null, null),
            new CalculatedPrice(5, 5, new CalculatedTaxCollection(), new TaxRuleCollection())
        );
    }
}
