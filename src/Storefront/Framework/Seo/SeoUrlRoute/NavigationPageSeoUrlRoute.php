<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Seo\SeoUrlRoute;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;

#[Package('inventory')]
class NavigationPageSeoUrlRoute implements SeoUrlRouteInterface
{
    final public const ROUTE_NAME = 'frontend.navigation.page';
    final public const DEFAULT_TEMPLATE = '{% for part in category.seoBreadcrumb %}{{ part }}/{% endfor %}';

    /**
     * @internal
     */
    public function __construct(
        private readonly CategoryDefinition $categoryDefinition,
        private readonly CategoryBreadcrumbBuilder $breadcrumbBuilder
    ) {
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->categoryDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, SalesChannelEntity $salesChannel): void
    {
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('active', true),
            new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsFilter('type', CategoryDefinition::TYPE_FOLDER),
                new EqualsFilter('type', CategoryDefinition::TYPE_LINK),
            ]),
        ]));
    }

    public function getMapping(Entity $category, ?SalesChannelEntity $salesChannel): SeoUrlMapping
    {
        if (!$category instanceof CategoryEntity) {
            throw new \InvalidArgumentException('Expected CategoryEntity');
        }

        $rootId = $this->detectRootId($category, $salesChannel);

        $breadcrumbs = $this->breadcrumbBuilder->build($category, $salesChannel, $rootId);
        $categoryJson = $category->jsonSerialize();
        $categoryJson['seoBreadcrumb'] = $breadcrumbs;

        $error = null;
        if (!$rootId) {
            $error = 'Category is not available for sales channel';
        }

        return new SeoUrlMapping(
            $category,
            ['navigationId' => $category->getId()],
            [
                'category' => $categoryJson,
            ],
            $error
        );
    }

    private function detectRootId(CategoryEntity $category, ?SalesChannelEntity $salesChannel): ?string
    {
        if (!$salesChannel) {
            return null;
        }
        $path = array_filter(explode('|', (string) $category->getPath()));

        $navigationId = $salesChannel->getNavigationCategoryId();
        if ($navigationId === $category->getId() || \in_array($navigationId, $path, true)) {
            return $navigationId;
        }

        $footerId = $salesChannel->getFooterCategoryId();
        if ($footerId === $category->getId() || \in_array($footerId, $path, true)) {
            return $footerId;
        }

        $serviceId = $salesChannel->getServiceCategoryId();
        if ($serviceId === $category->getId() || \in_array($serviceId, $path, true)) {
            return $serviceId;
        }

        return null;
    }
}
