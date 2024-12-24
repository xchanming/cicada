<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class CartOrderRouteResponse extends StoreApiResponse
{
    /**
     * @var OrderEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(OrderEntity $object)
    {
        parent::__construct($object);
    }

    public function getOrder(): OrderEntity
    {
        return $this->object;
    }
}
