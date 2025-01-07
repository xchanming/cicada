<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('discovery')]
class SitemapSalesChannelCriteriaEvent extends Event implements CicadaEvent
{
    public function __construct(
        private readonly Criteria $criteria,
        private readonly Context $context
    ) {
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
