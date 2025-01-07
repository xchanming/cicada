<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('discovery')]
class SalesChannelEntityAggregationResultLoadedEvent extends EntityAggregationResultLoadedEvent implements CicadaSalesChannelEvent
{
    private readonly SalesChannelContext $salesChannelContext;

    public function __construct(
        EntityDefinition $definition,
        AggregationResultCollection $result,
        SalesChannelContext $salesChannelContext
    ) {
        parent::__construct($definition, $result, $salesChannelContext->getContext());
        $this->salesChannelContext = $salesChannelContext;
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
