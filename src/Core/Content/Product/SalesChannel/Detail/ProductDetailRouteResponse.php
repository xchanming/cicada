<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Detail;

use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('inventory')]
class ProductDetailRouteResponse extends StoreApiResponse
{
    /**
     * @var ArrayStruct<string, mixed>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(
        SalesChannelProductEntity $product,
        ?PropertyGroupCollection $configurator
    ) {
        parent::__construct(new ArrayStruct([
            'product' => $product,
            'configurator' => $configurator,
        ], 'product_detail'));
    }

    /**
     * @return ArrayStruct<string, mixed>
     */
    public function getResult(): ArrayStruct
    {
        return $this->object;
    }

    public function getProduct(): SalesChannelProductEntity
    {
        return $this->object->get('product');
    }

    public function getConfigurator(): ?PropertyGroupCollection
    {
        return $this->object->get('configurator');
    }
}
