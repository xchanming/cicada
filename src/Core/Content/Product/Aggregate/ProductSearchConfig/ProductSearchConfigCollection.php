<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductSearchConfig;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductSearchConfigEntity>
 */
#[Package('inventory')]
class ProductSearchConfigCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_search_config_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductSearchConfigEntity::class;
    }
}
