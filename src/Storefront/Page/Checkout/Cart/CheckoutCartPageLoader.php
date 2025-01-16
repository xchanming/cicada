<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Cart;

use Cicada\Core\Checkout\Gateway\SalesChannel\AbstractCheckoutGatewayRoute;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\SalesChannel\AbstractCountryRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class CheckoutCartPageLoader
{
    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - translator will be mandatory from 6.7
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StorefrontCartFacade $cartService,
        private readonly AbstractCheckoutGatewayRoute $checkoutGatewayRoute,
        private readonly AbstractCountryRoute $countryRoute,
        private readonly ?AbstractTranslator $translator = null
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): CheckoutCartPage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = CheckoutCartPage::createFrom($page);
        $this->setMetaInformation($page);

        $page->setCountries($this->getCountries($salesChannelContext));

        $cart = $this->cartService->get($salesChannelContext->getToken(), $salesChannelContext);

        $gatewayResponse = $this->checkoutGatewayRoute->load($request, $cart, $salesChannelContext);

        $page->setPaymentMethods($gatewayResponse->getPaymentMethods());
        $page->setCart($cart);

        $shippingMethods = $gatewayResponse->getShippingMethods();

        if (!$shippingMethods->has($salesChannelContext->getShippingMethod()->getId())) {
            $shippingMethods->add($salesChannelContext->getShippingMethod());
        }

        $page->setShippingMethods($shippingMethods);

        $this->eventDispatcher->dispatch(
            new CheckoutCartPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(CheckoutCartPage $page): void
    {
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        if ($this->translator !== null && $page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        if ($this->translator !== null) {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('checkout.cartMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        }
    }

    private function getCountries(SalesChannelContext $context): CountryCollection
    {
        /**
         * @deprecated tag:v6.7.0 - remove Feature:isActive on release
         */
        if (Feature::isActive('v6.7.0.0') && $context->getCustomer()) {
            return new CountryCollection();
        }

        $countries = $this->countryRoute->load(new Request(), new Criteria(), $context)->getCountries();
        $countries->sortByPositionAndName();

        return $countries;
    }
}
