<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 *
 * @template TEntityCollection of EntityCollection
 */
#[Package('core')]
class EntityResult extends AggregationResult
{
    /**
     * @param TEntityCollection $entities
     */
    public function __construct(string $name, protected EntityCollection $entities)
    {
        parent::__construct($name);
    }

    /**
     * @return TEntityCollection
     */
    public function getEntities(): EntityCollection
    {
        return $this->entities;
    }

    public function add(Entity $entity): void
    {
        $this->entities->add($entity);
    }
}
