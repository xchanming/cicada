<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductSearchKeyword;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductSearchKeywordEntity>
 */
#[Package('inventory')]
class ProductSearchKeywordCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_search_keyword_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductSearchKeywordEntity::class;
    }
}
