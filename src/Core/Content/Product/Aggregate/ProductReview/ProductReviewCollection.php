<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductReview;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductReviewEntity>
 */
#[Package('after-sales')]
class ProductReviewCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_review_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductReviewEntity::class;
    }
}
