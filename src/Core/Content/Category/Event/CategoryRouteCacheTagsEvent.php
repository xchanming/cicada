<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('discovery')]
class CategoryRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
    /**
     * @param array<string> $tags
     */
    public function __construct(
        protected string $navigationId,
        array $tags,
        Request $request,
        StoreApiResponse $response,
        SalesChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($tags, $request, $response, $context, $criteria);
    }

    public function getNavigationId(): string
    {
        return $this->navigationId;
    }
}
