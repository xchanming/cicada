<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\Configurator;

use Cicada\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('storefront')]
class ProductPageConfiguratorLoader extends ProductConfiguratorLoader
{
    /**
     * @internal
     */
    public function __construct(private readonly ProductConfiguratorLoader $loader)
    {
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function load(SalesChannelProductEntity $product, SalesChannelContext $context): PropertyGroupCollection
    {
        return $this->loader->load($product, $context);
    }
}
