<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\SalesChannel;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('discovery')]
interface SalesChannelCmsPageLoaderInterface
{
    /**
     * @param array<string, mixed>|null $config
     *
     * @return EntitySearchResult<CmsPageCollection>
     */
    public function load(
        Request $request,
        Criteria $criteria,
        SalesChannelContext $context,
        ?array $config = null,
        ?ResolverContext $resolverContext = null
    ): EntitySearchResult;
}
