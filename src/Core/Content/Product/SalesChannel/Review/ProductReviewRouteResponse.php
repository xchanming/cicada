<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('after-sales')]
class ProductReviewRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<ProductReviewCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<ProductReviewCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    /**
     * @return EntitySearchResult<ProductReviewCollection>
     */
    public function getResult(): EntitySearchResult
    {
        return $this->object;
    }
}
