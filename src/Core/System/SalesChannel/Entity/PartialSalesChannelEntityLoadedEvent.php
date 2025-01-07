<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('discovery')]
class PartialSalesChannelEntityLoadedEvent extends SalesChannelEntityLoadedEvent
{
    /**
     * @var PartialEntity[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $entities;

    public function __construct(
        EntityDefinition $definition,
        array $entities,
        SalesChannelContext $context
    ) {
        parent::__construct($definition, $entities, $context);

        $this->name = $this->definition->getEntityName() . '.partial_loaded';
    }

    /**
     * @return PartialEntity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
