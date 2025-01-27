<?php

declare(strict_types=1);

namespace Cicada\Core\Content\Category\Cms;

use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class CategoryNavigationCmsElementResolver extends AbstractCmsElementResolver
{
    /**
     * @internal
     */
    public function __construct(
        private readonly NavigationLoaderInterface $navigationLoader,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getType(): string
    {
        return 'category-navigation';
    }

    /**
     * @codeCoverageIgnore
     */
    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $salesChannelContext = $resolverContext->getSalesChannelContext();
        $salesChannel = $salesChannelContext->getSalesChannel();

        $rootNavigationId = $salesChannel->getNavigationCategoryId();
        $navigationId = $resolverContext->getRequest()->get('navigationId', $rootNavigationId);

        $tree = $this->navigationLoader->load(
            $navigationId,
            $salesChannelContext,
            $rootNavigationId,
            $salesChannel->getNavigationCategoryDepth()
        );

        $slot->setData($tree);
    }
}
