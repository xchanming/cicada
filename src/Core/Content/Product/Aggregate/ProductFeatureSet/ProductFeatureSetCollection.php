<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductFeatureSet;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductFeatureSetEntity>
 */
#[Package('inventory')]
class ProductFeatureSetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductFeatureSetEntity::class;
    }
}
