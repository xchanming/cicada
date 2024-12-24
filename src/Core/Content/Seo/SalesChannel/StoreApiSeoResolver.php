<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SalesChannel;

use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface as SeoUrlRouteConfigRoute;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('buyers-experience')]
class StoreApiSeoResolver implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SalesChannelRepository $salesChannelRepository,
        private readonly DefinitionInstanceRegistry $definitionInstanceRegistry,
        private readonly SalesChannelDefinitionInstanceRegistry $salesChannelDefinitionInstanceRegistry,
        private readonly SeoUrlRouteRegistry $seoUrlRouteRegistry
    ) {
    }

    /**
     * This subscriber has to trigger before the {@see \Cicada\Core\System\SalesChannel\Api\StoreApiResponseListener},
     * because it requires access to the `StoreApiResponse`'s struct object, which is not available after encoding it.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addSeoInformation', 11000],
        ];
    }

    public function addSeoInformation(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof StoreApiResponse) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->headers->has(PlatformRequest::HEADER_INCLUDE_SEO_URLS)) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context instanceof SalesChannelContext) {
            // This is likely the case for routes with the `auth_required` option set to `false`,
            // where the sales-channel-id and context is not resolved by access-token by the other listeners.
            return;
        }

        $dataBag = new SeoResolverData();

        $this->find($dataBag, $response->getObject());
        $this->enrich($dataBag, $context);
    }

    private function find(SeoResolverData $data, Struct $struct): void
    {
        if ($struct instanceof AggregationResultCollection) {
            foreach ($struct as $item) {
                $this->findStruct($data, $item);
            }
        }

        if ($struct instanceof EntitySearchResult) {
            foreach ($struct->getEntities() as $entity) {
                $this->findStruct($data, $entity);
            }
        }

        if ($struct instanceof Collection) {
            foreach ($struct as $item) {
                $this->findStruct($data, $item);
            }
        }

        $this->findStruct($data, $struct);
    }

    private function findStruct(SeoResolverData $data, Struct $struct): void
    {
        if ($struct instanceof Entity) {
            $definition = $this->definitionInstanceRegistry->getByEntityClass($struct) ?? $this->salesChannelDefinitionInstanceRegistry->getByEntityClass($struct);
            if ($definition && $definition->isSeoAware()) {
                $data->add($definition->getEntityName(), $struct);
            }
        }

        foreach ($struct->getVars() as $item) {
            if ($item instanceof Collection) {
                foreach ($item as $collectionItem) {
                    if ($collectionItem instanceof Struct) {
                        $this->findStruct($data, $collectionItem);
                    }
                }
            } elseif ($item instanceof Struct) {
                $this->findStruct($data, $item);
            }
        }
    }

    private function enrich(SeoResolverData $data, SalesChannelContext $context): void
    {
        foreach ($data->getEntities() as $definition) {
            $definition = (string) $definition;

            $ids = $data->getIds($definition);
            $routes = $this->seoUrlRouteRegistry->findByDefinition($definition);
            if (\count($routes) === 0) {
                continue;
            }

            $routes = array_map(static fn (SeoUrlRouteConfigRoute $seoUrlRoute) => $seoUrlRoute->getConfig()->getRouteName(), $routes);

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('isCanonical', true));
            $criteria->addFilter(new EqualsAnyFilter('routeName', $routes));
            $criteria->addFilter(new EqualsAnyFilter('foreignKey', $ids));
            $criteria->addFilter(new EqualsFilter('languageId', $context->getContext()->getLanguageId()));
            $criteria->addSorting(new FieldSorting('salesChannelId'));

            /** @var SeoUrlEntity $url */
            foreach ($this->salesChannelRepository->search($criteria, $context) as $url) {
                /** @var SalesChannelProductEntity|CategoryEntity $entity */
                $entity = $data->get($definition, $url->getForeignKey());

                if ($entity->getSeoUrls() === null) {
                    $entity->setSeoUrls(new SeoUrlCollection());
                }

                /** @var SeoUrlCollection $seoUrlCollection */
                $seoUrlCollection = $entity->getSeoUrls();
                $seoUrlCollection->add($url);
            }
        }
    }
}
