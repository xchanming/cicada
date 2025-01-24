<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Country;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\SalesChannel\AbstractCountryStateRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageletLoader. Always use a store-api route to get or put data.
 */
#[Package('discovery')]
class CountryStateDataPageletLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractCountryStateRoute $countryStateRoute,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function load(string $countryId, Request $request, SalesChannelContext $context, ?string $parentId = null): CountryStateDataPagelet
    {
        $page = new CountryStateDataPagelet();

        $criteria = new Criteria();
        $criteria->addAssociation('children.children');
        $criteria->addFilter(new EqualsFilter('parentId', empty($parentId) ? null : $parentId));

        $this->eventDispatcher->dispatch(new CountryStateDataPageletCriteriaEvent($criteria, $context, $request));

        $countryRouteResponse = $this->countryStateRoute->load($countryId, $request, $criteria, $context);

        $page->setStates($countryRouteResponse->getStates());

        $this->eventDispatcher->dispatch(new CountryStateDataPageletLoadedEvent($page, $context, $request));

        return $page;
    }
}
