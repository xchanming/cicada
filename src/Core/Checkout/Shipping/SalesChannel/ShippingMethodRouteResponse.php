<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping\SalesChannel;

use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class ShippingMethodRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<ShippingMethodCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<ShippingMethodCollection> $shippingMethods
     */
    public function __construct(EntitySearchResult $shippingMethods)
    {
        parent::__construct($shippingMethods);
    }

    public function getShippingMethods(): ShippingMethodCollection
    {
        return $this->object->getEntities();
    }
}
