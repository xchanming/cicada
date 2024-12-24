<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class MaxResult extends AggregationResult
{
    public function __construct(
        string $name,
        protected string|float|int|null $max
    ) {
        parent::__construct($name);
    }

    public function getMax(): string|float|int|null
    {
        return $this->max;
    }
}
