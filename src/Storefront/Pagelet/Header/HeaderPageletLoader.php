<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Header;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Content\Category\Tree\TreeItem;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\Currency\SalesChannel\AbstractCurrencyRoute;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\SalesChannel\AbstractLanguageRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Event\RouteRequest\CurrencyRouteRequestEvent;
use Cicada\Storefront\Event\RouteRequest\LanguageRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageletLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class HeaderPageletLoader implements HeaderPageletLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractCurrencyRoute $currencyRoute,
        private readonly AbstractLanguageRoute $languageRoute,
        private readonly NavigationLoaderInterface $navigationLoader
    ) {
    }

    /**
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $context): HeaderPagelet
    {
        $salesChannel = $context->getSalesChannel();
        $navigationId = $request->get('navigationId', $salesChannel->getNavigationCategoryId());

        if (!$navigationId) {
            throw RoutingException::missingRequestParameter('navigationId');
        }

        $languages = $this->getLanguages($context, $request);
        $event = new CurrencyRouteRequestEvent($request, new Request(), $context);
        $this->eventDispatcher->dispatch($event);

        $navigation = $this->navigationLoader->load(
            (string) $navigationId,
            $context,
            $salesChannel->getNavigationCategoryId(),
            $salesChannel->getNavigationCategoryDepth()
        );

        $criteria = new Criteria();
        $criteria->setTitle('header::currencies');

        $currencies = $this->currencyRoute
            ->load($event->getStoreApiRequest(), $context, $criteria)
            ->getCurrencies();

        $contextLanguage = $languages->get($context->getContext()->getLanguageId());
        if (!$contextLanguage) {
            throw new \RuntimeException(\sprintf('Context language with id %s not found', $context->getContext()->getLanguageId()));
        }

        $page = new HeaderPagelet(
            $navigation,
            $languages,
            $currencies,
            $contextLanguage,
            $context->getCurrency(),
            $this->getServiceMenu($context)
        );

        $this->eventDispatcher->dispatch(new HeaderPageletLoadedEvent($page, $context, $request));

        return $page;
    }

    private function getServiceMenu(SalesChannelContext $context): CategoryCollection
    {
        $serviceId = $context->getSalesChannel()->getServiceCategoryId();

        if ($serviceId === null) {
            return new CategoryCollection();
        }

        $navigation = $this->navigationLoader->load($serviceId, $context, $serviceId, 1);

        return new CategoryCollection(array_map(static fn (TreeItem $treeItem) => $treeItem->getCategory(), $navigation->getTree()));
    }

    private function getLanguages(SalesChannelContext $context, Request $request): LanguageCollection
    {
        $criteria = new Criteria();
        $criteria->setTitle('header::languages');

        $criteria->addFilter(
            new EqualsFilter('language.salesChannelDomains.salesChannelId', $context->getSalesChannelId())
        );

        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $criteria->addAssociation('productSearchConfig');
        $apiRequest = new Request();

        $event = new LanguageRouteRequestEvent($request, $apiRequest, $context, $criteria);
        $this->eventDispatcher->dispatch($event);

        return $this->languageRoute->load($event->getStoreApiRequest(), $context, $criteria)->getLanguages();
    }
}
