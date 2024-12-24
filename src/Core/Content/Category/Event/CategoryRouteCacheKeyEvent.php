<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class CategoryRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    /**
     * @param array<mixed> $parts
     */
    public function __construct(
        protected string $navigationId,
        array $parts,
        Request $request,
        SalesChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($parts, $request, $context, $criteria);
    }

    public function getNavigationId(): string
    {
        return $this->navigationId;
    }
}
