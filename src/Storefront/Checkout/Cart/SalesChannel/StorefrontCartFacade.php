<?php declare(strict_types=1);

namespace Cicada\Storefront\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Cicada\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Cicada\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Cicada\Storefront\Checkout\Payment\BlockedPaymentMethodSwitcher;
use Cicada\Storefront\Checkout\Shipping\BlockedShippingMethodSwitcher;

#[Package('checkout')]
class StorefrontCartFacade
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly BlockedShippingMethodSwitcher $blockedShippingMethodSwitcher,
        private readonly BlockedPaymentMethodSwitcher $blockedPaymentMethodSwitcher,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly CartCalculator $calculator,
        private readonly AbstractCartPersister $cartPersister
    ) {
    }

    public function get(
        string $token,
        SalesChannelContext $originalContext,
        bool $caching = true,
        bool $taxed = false
    ): Cart {
        $originalCart = $this->cartService->getCart($token, $originalContext, $caching, $taxed);
        $cartErrors = $originalCart->getErrors();
        if (!$this->cartContainsBlockedMethods($cartErrors)) {
            return $originalCart;
        }

        // Switch shipping method if blocked
        $contextShippingMethod = $this->blockedShippingMethodSwitcher->switch($cartErrors, $originalContext);

        // Switch payment method if blocked
        $contextPaymentMethod = $this->blockedPaymentMethodSwitcher->switch($cartErrors, $originalContext);

        if ($contextShippingMethod->getId() === $originalContext->getShippingMethod()->getId()
            && $contextPaymentMethod->getId() === $originalContext->getPaymentMethod()->getId()
        ) {
            return $originalCart;
        }

        $updatedContext = clone $originalContext;
        $updatedContext->assign([
            'shippingMethod' => $contextShippingMethod,
            'paymentMethod' => $contextPaymentMethod,
        ]);

        $newCart = $this->calculator->calculate($originalCart, $updatedContext);

        // Recalculated cart successfully unblocked
        if (!$this->cartContainsBlockedMethods($newCart->getErrors())) {
            $this->cartPersister->save($newCart, $updatedContext);
            $this->updateSalesChannelContext($updatedContext);

            return $newCart;
        }

        // Recalculated cart contains one or more blocked shipping/payment method, rollback changes
        $this->removeSwitchNotices($cartErrors);

        return $originalCart;
    }

    private function cartContainsBlockedMethods(ErrorCollection $errors): bool
    {
        foreach ($errors as $error) {
            if ($error instanceof ShippingMethodBlockedError || $error instanceof PaymentMethodBlockedError) {
                return true;
            }
        }

        return false;
    }

    private function updateSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([
                SalesChannelContextService::SHIPPING_METHOD_ID => $salesChannelContext->getShippingMethod()->getId(),
                SalesChannelContextService::PAYMENT_METHOD_ID => $salesChannelContext->getPaymentMethod()->getId(),
            ]),
            $salesChannelContext
        );
    }

    /**
     * Remove all PaymentMethodChangedErrors and ShippingMethodChangedErrors from cart
     */
    private function removeSwitchNotices(ErrorCollection $cartErrors): void
    {
        foreach ($cartErrors as $error) {
            if (!$error instanceof ShippingMethodChangedError && !$error instanceof PaymentMethodChangedError) {
                continue;
            }

            if ($error instanceof ShippingMethodChangedError) {
                $cartErrors->add(new ShippingMethodBlockedError($error->getOldShippingMethodName()));
            }

            if ($error instanceof PaymentMethodChangedError) {
                $cartErrors->add(new PaymentMethodBlockedError($error->getOldPaymentMethodName()));
            }

            $cartErrors->remove($error->getId());
        }
    }
}
