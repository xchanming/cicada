<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('discovery')]
class SalesChannelEntityIdSearchResultLoadedEvent extends EntityIdSearchResultLoadedEvent implements CicadaSalesChannelEvent
{
    public function __construct(
        EntityDefinition $definition,
        IdSearchResult $result,
        private readonly SalesChannelContext $salesChannelContext
    ) {
        parent::__construct($definition, $result);
    }

    public function getName(): string
    {
        return 'sales_channel.' . parent::getName();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
