<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Checkout;

use Cicada\Core\Checkout\Cart\Address\Error\AddressValidationError;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Cicada\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Cicada\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Cicada\Tests\Integration\Storefront\Page\StorefrontPageTestConstants;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CheckoutConfirmPage::class)]
class ConfirmPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItLoadsTheConfirmPage(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $event = null;
        $this->catchEvent(CheckoutConfirmPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertSame(0.0, $page->getCart()->getPrice()->getNetPrice());
        static::assertSame($context->getToken(), $page->getCart()->getToken());
        static::assertCount(StorefrontPageTestConstants::AVAILABLE_SHIPPING_METHOD_COUNT, $page->getShippingMethods());
        static::assertCount(StorefrontPageTestConstants::AVAILABLE_PAYMENT_METHOD_COUNT, $page->getPaymentMethods());
        static::assertNotEmpty($page->getPaymentMethods());
        self::assertPageEvent(CheckoutConfirmPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testItIgnoresUnavailableShippingMethods(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        /** @var EntityRepository<ShippingMethodCollection> $shippingMethodRepository */
        $shippingMethodRepository = static::getContainer()->get('shipping_method.repository');
        $shippingMethods = $shippingMethodRepository->search(new Criteria(), $context->getContext())->getEntities();

        $updates = [];

        foreach ($shippingMethods as $shippingMethod) {
            $updates[] = [
                'id' => $shippingMethod->getId(),
                'availabilityRule' => [
                    'name' => 'test',
                    'priority' => 0,
                ],
            ];
        }

        $shippingMethodRepository->update($updates, $context->getContext());

        $event = null;
        $this->catchEvent(CheckoutConfirmPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertCount(0, $page->getShippingMethods());
        self::assertPageEvent(CheckoutConfirmPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testItIgnoresUnavailablePaymentMethods(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        /** @var EntityRepository<PaymentMethodCollection> $paymentMethodRepository */
        $paymentMethodRepository = static::getContainer()->get('payment_method.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
        $paymentMethod = $paymentMethodRepository->search($criteria, $context->getContext())->getEntities()->first();
        static::assertNotNull($paymentMethod);

        $paymentMethodRepository->update(
            [
                ['id' => $paymentMethod->getId(), 'availabilityRule' => ['name' => 'invalid', 'priority' => 0]],
            ],
            $context->getContext()
        );

        $event = null;
        $this->catchEvent(CheckoutConfirmPageLoadedEvent::class, $event);

        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $page = $this->getPageLoader()->load($request, $context);

        static::assertCount(StorefrontPageTestConstants::AVAILABLE_PAYMENT_METHOD_COUNT - 1, $page->getPaymentMethods());
        self::assertPageEvent(CheckoutConfirmPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testCartErrorAddedOnInvalidAddress(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $customer = $context->getCustomer();
        static::assertNotNull($customer);
        $activeBillingAddress = $customer->getActiveBillingAddress();
        static::assertNotNull($activeBillingAddress);

        $newShippingAddress = clone $activeBillingAddress;

        $activeBillingAddress->setName('');
        $newShippingAddress->setId(Uuid::randomHex());
        $customer->setActiveShippingAddress($newShippingAddress);

        $cartErrors = $this->getPageLoader()->load($request, $context)->getCart()->getErrors();
        static::assertCount(2, $cartErrors);
        $errors = $cartErrors->getElements();
        static::assertArrayHasKey('billing-address-invalid', $errors);
        static::assertArrayHasKey('shipping-address-invalid', $errors);

        $billingAddressViolation = $errors['billing-address-invalid'];
        static::assertInstanceOf(AddressValidationError::class, $billingAddressViolation);
        $violation = $billingAddressViolation->getViolations()->get(0);
        static::assertSame('/name', $violation->getPropertyPath());

        $shippingAddressViolation = $errors['shipping-address-invalid'];
        static::assertInstanceOf(AddressValidationError::class, $shippingAddressViolation);
        $violation = $shippingAddressViolation->getViolations()->get(0);
        static::assertSame('/lastName', $violation->getPropertyPath());
    }

    protected function getPageLoader(): CheckoutConfirmPageLoader
    {
        return static::getContainer()->get(CheckoutConfirmPageLoader::class);
    }
}
