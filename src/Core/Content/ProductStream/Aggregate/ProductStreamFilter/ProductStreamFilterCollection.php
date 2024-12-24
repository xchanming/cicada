<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\Aggregate\ProductStreamFilter;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductStreamFilterEntity>
 */
#[Package('inventory')]
class ProductStreamFilterCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_stream_filter_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductStreamFilterEntity::class;
    }
}
