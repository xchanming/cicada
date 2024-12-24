<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\QuickView;

use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('storefront')]
class MinimalQuickViewPage extends Struct
{
    /**
     * @var ProductEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $product;

    /**
     * @internal
     */
    public function __construct(ProductEntity $product)
    {
        $this->product = $product;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }
}
