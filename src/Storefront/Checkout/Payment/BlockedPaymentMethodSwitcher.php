<?php declare(strict_types=1);

namespace Cicada\Storefront\Checkout\Payment;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal Only to be used by the Storefront
 */
#[Package('checkout')]
class BlockedPaymentMethodSwitcher
{
    public function __construct(private readonly AbstractPaymentMethodRoute $paymentMethodRoute)
    {
    }

    public function switch(ErrorCollection $errors, SalesChannelContext $salesChannelContext): PaymentMethodEntity
    {
        $originalPaymentMethod = $salesChannelContext->getPaymentMethod();
        if (!$this->paymentMethodBlocked($errors)) {
            return $originalPaymentMethod;
        }

        $paymentMethod = $this->getPaymentMethodToChangeTo($errors, $salesChannelContext);
        if ($paymentMethod === null) {
            return $originalPaymentMethod;
        }

        $this->addNoticeToCart($errors, $paymentMethod);

        return $paymentMethod;
    }

    private function paymentMethodBlocked(ErrorCollection $errors): bool
    {
        foreach ($errors as $error) {
            if ($error instanceof PaymentMethodBlockedError) {
                return true;
            }
        }

        return false;
    }

    private function getPaymentMethodToChangeTo(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ?PaymentMethodEntity
    {
        $blockedPaymentMethodNames = $errors->fmap(static fn (Error $error) => $error instanceof PaymentMethodBlockedError ? $error->getName() : null);

        $request = new Request(['onlyAvailable' => true]);
        $defaultPaymentMethod = $this->paymentMethodRoute->load(
            $request,
            $salesChannelContext,
            new Criteria([$salesChannelContext->getSalesChannel()->getPaymentMethodId()])
        )->getPaymentMethods()->first();

        if ($defaultPaymentMethod !== null && !\in_array($defaultPaymentMethod->getName(), $blockedPaymentMethodNames, true)) {
            return $defaultPaymentMethod;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new NandFilter([
                new EqualsAnyFilter('name', $blockedPaymentMethodNames),
            ])
        );

        return $this->paymentMethodRoute->load(
            $request,
            $salesChannelContext,
            $criteria
        )->getPaymentMethods()->first();
    }

    private function addNoticeToCart(ErrorCollection $cartErrors, PaymentMethodEntity $paymentMethod): void
    {
        $newPaymentMethodName = $paymentMethod->getTranslation('name');
        if ($newPaymentMethodName === null) {
            return;
        }

        foreach ($cartErrors as $error) {
            if (!$error instanceof PaymentMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $cartErrors->remove($error->getId());
            $cartErrors->add(new PaymentMethodChangedError(
                $error->getName(),
                $newPaymentMethodName
            ));
        }
    }
}
