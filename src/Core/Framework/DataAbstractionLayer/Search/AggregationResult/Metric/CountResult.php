<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class CountResult extends AggregationResult
{
    public function __construct(
        string $name,
        protected int $count
    ) {
        parent::__construct($name);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
