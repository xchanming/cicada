<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Offcanvas;

use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class OffcanvasCartPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StorefrontCartFacade $cartService,
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): OffcanvasCartPage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = OffcanvasCartPage::createFrom($page);

        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->assign(['robots' => 'noindex,follow']);
        }

        $page->setCart($this->cartService->get($salesChannelContext->getToken(), $salesChannelContext));

        $page->setShippingMethods($this->getShippingMethods($salesChannelContext));

        $this->eventDispatcher->dispatch(
            new OffcanvasCartPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    private function getShippingMethods(SalesChannelContext $context): ShippingMethodCollection
    {
        $request = new Request();
        $request->query->set('onlyAvailable', '1');

        return $this->shippingMethodRoute->load($request, $context, new Criteria())->getShippingMethods();
    }
}
