<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class NavigationRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    /**
     * @param array<mixed> $parts
     */
    public function __construct(
        array $parts,
        protected string $active,
        protected string $rootId,
        protected int $depth,
        Request $request,
        SalesChannelContext $context,
        Criteria $criteria
    ) {
        parent::__construct($parts, $request, $context, $criteria);
    }

    public function getActive(): string
    {
        return $this->active;
    }

    public function getRootId(): string
    {
        return $this->rootId;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}
