<?php declare(strict_types=1);

namespace Cicada\Elasticsearch\Product;

use OpenSearchDSL\Query\Compound\BoolQuery;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class AbstractProductSearchQueryBuilder
{
    abstract public function getDecorated(): AbstractProductSearchQueryBuilder;

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - will return BuilderInterface in the future
     */
    abstract public function build(Criteria $criteria, Context $context): BoolQuery;
}
