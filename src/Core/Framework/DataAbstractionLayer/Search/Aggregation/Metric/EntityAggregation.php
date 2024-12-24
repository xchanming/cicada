<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Aggregation;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class EntityAggregation extends Aggregation
{
    public function __construct(
        string $name,
        string $field,
        protected readonly string $entity
    ) {
        parent::__construct($name, $field);
    }

    public function getEntity(): string
    {
        return $this->entity;
    }
}
