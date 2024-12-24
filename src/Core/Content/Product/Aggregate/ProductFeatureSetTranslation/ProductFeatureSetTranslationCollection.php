<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductFeatureSetTranslationEntity>
 */
#[Package('inventory')]
class ProductFeatureSetTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductFeatureSetTranslationEntity::class;
    }
}
