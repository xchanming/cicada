<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductExportEntity>
 */
#[Package('inventory')]
class ProductExportCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'product_export_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductExportEntity::class;
    }
}
