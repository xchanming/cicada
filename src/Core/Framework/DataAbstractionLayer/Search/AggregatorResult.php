<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @final
 */
#[Package('core')]
class AggregatorResult extends Struct
{
    public function __construct(
        private readonly AggregationResultCollection $aggregations,
        private readonly Context $context,
        private readonly Criteria $criteria
    ) {
    }

    public function getAggregations(): AggregationResultCollection
    {
        return $this->aggregations;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getApiAlias(): string
    {
        return 'dal_aggregator_result';
    }
}
