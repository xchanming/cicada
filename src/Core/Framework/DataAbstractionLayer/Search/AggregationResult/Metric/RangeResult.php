<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class RangeResult extends AggregationResult
{
    /**
     * @param array<string, int> $ranges
     */
    public function __construct(
        string $name,
        protected array $ranges
    ) {
        parent::__construct($name);
    }

    /**
     * @return array<string, int>
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }
}
