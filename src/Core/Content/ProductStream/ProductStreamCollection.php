<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductStreamEntity>
 */
#[Package('inventory')]
class ProductStreamCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_stream_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductStreamEntity::class;
    }
}
