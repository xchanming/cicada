<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Checkout\Cart\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Cicada\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Cicada\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Cicada\Storefront\Checkout\Payment\BlockedPaymentMethodSwitcher;
use Cicada\Storefront\Checkout\Shipping\BlockedShippingMethodSwitcher;

/**
 * @internal
 */
#[CoversClass(StorefrontCartFacade::class)]
class StorefrontCartFacadeTest extends TestCase
{
    public function testGetNoBlockedMethods(): void
    {
        $cart = $this->getCart();
        $cart->setErrors($this->getCartErrorCollection());

        $cartFacade = $this->getStorefrontCartFacade($cart);
        $salesChannelContext = $this->getSalesChannelContext();
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        static::assertEquals($this->getCart(), $returnedCart);
    }

    public function testGetBlockedShippingMethodAllowFallback(): void
    {
        $errorCollection = $this->getCartErrorCollection(true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $shippingMethod = $newMethods['shippingMethod'];
                    static::assertInstanceOf(ShippingMethodEntity::class, $shippingMethod);
                    static::assertSame('fallback-shipping-method-name', $shippingMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade(
            $cart,
            $this->callbackShippingMethodSwitcherReturnFallbackMethod(...)
        );
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        $filtered = $errorCollection->filterInstance(ShippingMethodChangedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(ShippingMethodChangedError::class, $error);
        static::assertSame([
            'newShippingMethodName' => 'fallback-shipping-method-name',
            'oldShippingMethodName' => 'original-shipping-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(true));
        static::assertNotEquals($controlCart, $returnedCart);
    }

    public function testGetBlockedPaymentMethodAllowFallback(): void
    {
        $errorCollection = $this->getCartErrorCollection(false, true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $paymentMethod = $newMethods['paymentMethod'];
                    static::assertInstanceOf(PaymentMethodEntity::class, $paymentMethod);
                    static::assertSame('fallback-payment-method-name', $paymentMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade(
            $cart,
            $this->callbackShippingMethodSwitcherReturnOriginalMethod(...),
            $this->callbackPaymentMethodSwitcherReturnFallbackMethod(...)
        );
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        $filtered = $errorCollection->filterInstance(PaymentMethodChangedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(PaymentMethodChangedError::class, $error);
        static::assertSame([
            'newPaymentMethodName' => 'fallback-payment-method-name',
            'oldPaymentMethodName' => 'original-payment-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(false, true));
        static::assertNotEquals($controlCart, $returnedCart);
    }

    public function testGetBlockedPaymentAndShippingMethodAllowFallback(): void
    {
        $errorCollection = $this->getCartErrorCollection(true, true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $paymentMethod = $newMethods['paymentMethod'];
                    static::assertInstanceOf(PaymentMethodEntity::class, $paymentMethod);
                    static::assertSame('fallback-payment-method-name', $paymentMethod->getName());
                    $shippingMethod = $newMethods['shippingMethod'];
                    static::assertInstanceOf(ShippingMethodEntity::class, $shippingMethod);
                    static::assertSame('fallback-shipping-method-name', $shippingMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade(
            $cart,
            $this->callbackShippingMethodSwitcherReturnFallbackMethod(...),
            $this->callbackPaymentMethodSwitcherReturnFallbackMethod(...)
        );
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        $filtered = $errorCollection->filterInstance(PaymentMethodChangedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(PaymentMethodChangedError::class, $error);
        static::assertSame([
            'newPaymentMethodName' => 'fallback-payment-method-name',
            'oldPaymentMethodName' => 'original-payment-method-name',
        ], $error->getParameters());

        $filtered = $errorCollection->filterInstance(ShippingMethodChangedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(ShippingMethodChangedError::class, $error);
        static::assertSame([
            'newShippingMethodName' => 'fallback-shipping-method-name',
            'oldShippingMethodName' => 'original-shipping-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(true, true));
        static::assertNotEquals($controlCart, $returnedCart);
    }

    public function testGetBlockedShippingMethodNoFallback(): void
    {
        $errorCollection = $this->getCartErrorCollection(true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $shippingMethod = $newMethods['shippingMethod'];
                    static::assertInstanceOf(ShippingMethodEntity::class, $shippingMethod);
                    static::assertSame('original-shipping-method-name', $shippingMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade($cart);
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        static::assertCount(0, $errorCollection->filterInstance(ShippingMethodChangedError::class));
        $filtered = $errorCollection->filterInstance(ShippingMethodBlockedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(ShippingMethodBlockedError::class, $error);
        static::assertSame([
            'name' => 'original-shipping-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(true));
        static::assertEquals($controlCart, $returnedCart);
    }

    public function testGetBlockedPaymentMethodNoFallback(): void
    {
        $errorCollection = $this->getCartErrorCollection(false, true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $paymentMethod = $newMethods['paymentMethod'];
                    static::assertInstanceOf(PaymentMethodEntity::class, $paymentMethod);
                    static::assertSame('original-payment-method-name', $paymentMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade($cart);
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        static::assertCount(0, $errorCollection->filterInstance(PaymentMethodChangedError::class));
        $filtered = $errorCollection->filterInstance(PaymentMethodBlockedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(PaymentMethodBlockedError::class, $error);
        static::assertSame([
            'name' => 'original-payment-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(false, true));
        static::assertEquals($controlCart, $returnedCart);
    }

    public function testGetUnswitchableCart(): void
    {
        $errorCollection = $this->getCartErrorCollection(true, true);

        $cart = $this->getCart();
        $cart->setErrors($errorCollection);

        $salesChannelContext = $this->getSalesChannelContext();
        $salesChannelContext
            ->method('assign')
            ->willReturnCallback(
                function ($newMethods): void {
                    $paymentMethod = $newMethods['paymentMethod'];
                    static::assertInstanceOf(PaymentMethodEntity::class, $paymentMethod);
                    static::assertSame('fallback-payment-method-name', $paymentMethod->getName());

                    $shippingMethod = $newMethods['shippingMethod'];
                    static::assertInstanceOf(ShippingMethodEntity::class, $shippingMethod);
                    static::assertSame('fallback-shipping-method-name', $shippingMethod->getName());
                }
            );

        $cartFacade = $this->getStorefrontCartFacade(
            $cart,
            $this->callbackShippingMethodSwitcherUnswitchableCart(...),
            $this->callbackPaymentMethodSwitcherUnswitchableCart(...)
        );
        $returnedCart = $cartFacade->get('', $salesChannelContext);

        static::assertCount(0, $errorCollection->filterInstance(ShippingMethodChangedError::class));
        $filtered = $errorCollection->filterInstance(ShippingMethodBlockedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(ShippingMethodBlockedError::class, $error);
        static::assertSame([
            'name' => 'original-shipping-method-name',
        ], $error->getParameters());

        static::assertCount(0, $errorCollection->filterInstance(PaymentMethodChangedError::class));
        $filtered = $errorCollection->filterInstance(PaymentMethodBlockedError::class);
        static::assertCount(1, $filtered);
        $error = $filtered->first();
        static::assertInstanceOf(PaymentMethodBlockedError::class, $error);
        static::assertSame([
            'name' => 'original-payment-method-name',
        ], $error->getParameters());

        $controlCart = $this->getCart();
        $controlCart->setErrors($this->getCartErrorCollection(true, true));
        static::assertEquals($controlCart, $returnedCart);
    }

    public function testCartServiceIsCalledTaxedAndWithNoCaching(): void
    {
        $cartService = static::createMock(CartService::class);
        $cartService
            ->expects(static::once())
            ->method('getCart')
            ->with(
                'token',
                static::isInstanceOf(SalesChannelContext::class),
                false,
                true
            );

        $cartFacade = new StorefrontCartFacade(
            $cartService,
            static::createMock(BlockedShippingMethodSwitcher::class),
            static::createMock(BlockedPaymentMethodSwitcher::class),
            static::createMock(ContextSwitchRoute::class),
            static::createMock(CartCalculator::class),
            static::createMock(AbstractCartPersister::class),
        );

        $cartFacade->get(
            'token',
            static::createMock(SalesChannelContext::class),
            false,
            true
        );
    }

    public function callbackShippingMethodSwitcherReturnOriginalMethod(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ShippingMethodEntity
    {
        return $salesChannelContext->getShippingMethod();
    }

    public function callbackShippingMethodSwitcherReturnFallbackMethod(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ShippingMethodEntity
    {
        foreach ($errors as $error) {
            if (!$error instanceof ShippingMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $errors->remove($error->getId());
            $errors->add(new ShippingMethodChangedError(
                $error->getName(),
                'fallback-shipping-method-name'
            ));
        }

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('fallback-shipping-method-id');
        $shippingMethod->setName('fallback-shipping-method-name');

        return $shippingMethod;
    }

    public function callbackShippingMethodSwitcherUnswitchableCart(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ShippingMethodEntity
    {
        foreach ($errors as $error) {
            if (!$error instanceof ShippingMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $errors->add(new ShippingMethodChangedError(
                $error->getName(),
                'fallback-shipping-method-name'
            ));
        }

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('fallback-shipping-method-id');
        $shippingMethod->setName('fallback-shipping-method-name');

        return $shippingMethod;
    }

    public function callbackPaymentMethodSwitcherReturnOriginalMethod(ErrorCollection $errors, SalesChannelContext $salesChannelContext): PaymentMethodEntity
    {
        return $salesChannelContext->getPaymentMethod();
    }

    public function callbackPaymentMethodSwitcherReturnFallbackMethod(ErrorCollection $errors, SalesChannelContext $salesChannelContext): PaymentMethodEntity
    {
        foreach ($errors as $error) {
            if (!$error instanceof PaymentMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $errors->remove($error->getId());
            $errors->add(new PaymentMethodChangedError(
                $error->getName(),
                'fallback-payment-method-name'
            ));
        }

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId('fallback-payment-method-id');
        $paymentMethod->setName('fallback-payment-method-name');

        return $paymentMethod;
    }

    public function callbackPaymentMethodSwitcherUnswitchableCart(ErrorCollection $errors, SalesChannelContext $salesChannelContext): PaymentMethodEntity
    {
        foreach ($errors as $error) {
            if (!$error instanceof PaymentMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $errors->add(new PaymentMethodChangedError(
                $error->getName(),
                'fallback-payment-method-name'
            ));
        }

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId('fallback-payment-method-id');
        $paymentMethod->setName('fallback-payment-method-name');

        return $paymentMethod;
    }

    private function getCart(): Cart
    {
        $cart = new Cart('cart-token');
        $cart->add(
            (new LineItem('line-item-id-1', 'line-item-type-1'))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('line-item-label-1')
                ->assign(['uniqueIdentifier' => 'line-item-id-1'])
        )->add(
            (new LineItem('line-item-id-2', 'line-item-type-2'))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('line-item-label-2')
                ->assign(['uniqueIdentifier' => 'line-item-id-2'])
        );

        return $cart;
    }

    private function getCartErrorCollection(bool $blockShippingMethod = false, bool $blockPaymentMethod = false): ErrorCollection
    {
        $cartErrors = new ErrorCollection();
        if ($blockShippingMethod) {
            $cartErrors->add(
                new ShippingMethodBlockedError(
                    'original-shipping-method-name'
                )
            );
        }

        if ($blockPaymentMethod) {
            $cartErrors->add(
                new PaymentMethodBlockedError(
                    'original-payment-method-name',
                    ''
                )
            );
        }

        return $cartErrors;
    }

    /**
     * @param callable(ErrorCollection, SalesChannelContext): ShippingMethodEntity|null $shippingSwitcherCallbackMethod
     * @param callable(ErrorCollection, SalesChannelContext): PaymentMethodEntity|null $paymentSwitcherCallbackMethod
     */
    private function getStorefrontCartFacade(Cart $cart, ?callable $shippingSwitcherCallbackMethod = null, ?callable $paymentSwitcherCallbackMethod = null): StorefrontCartFacade
    {
        $cartService = $this->createMock(CartService::class);
        $cartService->method('getCart')->willReturn($cart);

        $blockedShippingMethodSwitcher = $this->createMock(BlockedShippingMethodSwitcher::class);
        $blockedShippingMethodSwitcher->method('switch')->willReturnCallback($shippingSwitcherCallbackMethod ?? $this->callbackShippingMethodSwitcherReturnOriginalMethod(...));

        $blockedPaymentMethodSwitcher = $this->createMock(BlockedPaymentMethodSwitcher::class);
        $blockedPaymentMethodSwitcher->method('switch')->willReturnCallback($paymentSwitcherCallbackMethod ?? $this->callbackPaymentMethodSwitcherReturnOriginalMethod(...));

        $contextSwitchRoute = $this->createMock(ContextSwitchRoute::class);

        $cartCalculator = $this->createMock(CartCalculator::class);
        $cartCalculator->method('calculate')->willReturnArgument(0);

        $cartPersister = $this->createMock(CartPersister::class);

        return new StorefrontCartFacade(
            $cartService,
            $blockedShippingMethodSwitcher,
            $blockedPaymentMethodSwitcher,
            $contextSwitchRoute,
            $cartCalculator,
            $cartPersister,
        );
    }

    /**
     * @return MockObject&SalesChannelContext
     */
    private function getSalesChannelContext(): MockObject
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannel->setLanguageId(Defaults::LANGUAGE_SYSTEM);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannel')->willReturn($salesChannel);
        $salesChannelContext->method('getContext')->willReturn(Context::createDefaultContext());

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('original-shipping-method-id');
        $shippingMethod->setName('original-shipping-method-name');

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId('original-payment-method-id');
        $paymentMethod->setName('original-payment-method-name');

        $salesChannelContext->method('getShippingMethod')->willReturn($shippingMethod);
        $salesChannelContext->method('getPaymentMethod')->willReturn($paymentMethod);

        return $salesChannelContext;
    }
}
