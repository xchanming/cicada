<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @template TEntityCollection of EntityCollection
 *
 * @extends EntitySearchResultLoadedEvent<TEntityCollection>
 */
#[Package('discovery')]
class SalesChannelEntitySearchResultLoadedEvent extends EntitySearchResultLoadedEvent implements CicadaSalesChannelEvent
{
    /**
     * @param EntitySearchResult<TEntityCollection> $result
     */
    public function __construct(
        EntityDefinition $definition,
        EntitySearchResult $result,
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
