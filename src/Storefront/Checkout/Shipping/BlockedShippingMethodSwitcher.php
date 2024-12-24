<?php declare(strict_types=1);

namespace Cicada\Storefront\Checkout\Shipping;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal Only to be used by the Storefront
 */
#[Package('checkout')]
class BlockedShippingMethodSwitcher
{
    public function __construct(private readonly AbstractShippingMethodRoute $shippingMethodRoute)
    {
    }

    public function switch(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ShippingMethodEntity
    {
        $originalShippingMethod = $salesChannelContext->getShippingMethod();
        if (!$this->shippingMethodBlocked($errors)) {
            return $originalShippingMethod;
        }

        $shippingMethod = $this->getShippingMethodToChangeTo($errors, $salesChannelContext);
        if ($shippingMethod === null) {
            return $originalShippingMethod;
        }

        $this->addNoticeToCart($errors, $shippingMethod);

        return $shippingMethod;
    }

    private function shippingMethodBlocked(ErrorCollection $cartErrors): bool
    {
        foreach ($cartErrors as $error) {
            if ($error instanceof ShippingMethodBlockedError) {
                return true;
            }
        }

        return false;
    }

    private function getShippingMethodToChangeTo(ErrorCollection $errors, SalesChannelContext $salesChannelContext): ?ShippingMethodEntity
    {
        $blockedShippingMethodNames = $errors->fmap(static fn (Error $error) => $error instanceof ShippingMethodBlockedError ? $error->getName() : null);

        $request = new Request(['onlyAvailable' => true]);
        $defaultShippingMethod = $this->shippingMethodRoute->load(
            $request,
            $salesChannelContext,
            new Criteria([$salesChannelContext->getSalesChannel()->getShippingMethodId()])
        )->getShippingMethods()->first();

        if ($defaultShippingMethod !== null && !\in_array($defaultShippingMethod->getName(), $blockedShippingMethodNames, true)) {
            return $defaultShippingMethod;
        }

        // Default excluded take next shipping method
        $criteria = new Criteria();
        $criteria->addFilter(
            new NandFilter([
                new EqualsAnyFilter('name', $blockedShippingMethodNames),
            ]),
        );

        return $this->shippingMethodRoute->load(
            $request,
            $salesChannelContext,
            $criteria
        )->getShippingMethods()->first();
    }

    private function addNoticeToCart(ErrorCollection $cartErrors, ShippingMethodEntity $shippingMethod): void
    {
        $newShippingMethodName = $shippingMethod->getTranslation('name');
        if ($newShippingMethodName === null) {
            return;
        }

        foreach ($cartErrors as $error) {
            if (!$error instanceof ShippingMethodBlockedError) {
                continue;
            }

            // Exchange cart blocked warning with notice
            $cartErrors->remove($error->getId());
            $cartErrors->add(new ShippingMethodChangedError(
                $error->getName(),
                $newShippingMethodName
            ));
        }
    }
}
