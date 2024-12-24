<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Delivery\Struct;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('checkout')]
class Delivery extends Struct
{
    /**
     * @var DeliveryPositionCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $positions;

    /**
     * @var ShippingLocation
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $location;

    /**
     * @var DeliveryDate
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $deliveryDate;

    /**
     * @var ShippingMethodEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingMethod;

    /**
     * @var CalculatedPrice
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingCosts;

    public function __construct(
        DeliveryPositionCollection $positions,
        DeliveryDate $deliveryDate,
        ShippingMethodEntity $shippingMethod,
        ShippingLocation $location,
        CalculatedPrice $shippingCosts
    ) {
        $this->location = $location;
        $this->positions = $positions;
        $this->deliveryDate = $deliveryDate;
        $this->shippingMethod = $shippingMethod;
        $this->shippingCosts = $shippingCosts;
    }

    public function getPositions(): DeliveryPositionCollection
    {
        return $this->positions;
    }

    public function getLocation(): ShippingLocation
    {
        return $this->location;
    }

    public function getDeliveryDate(): DeliveryDate
    {
        return $this->deliveryDate;
    }

    public function getShippingMethod(): ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethodEntity $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getShippingCosts(): CalculatedPrice
    {
        return $this->shippingCosts;
    }

    public function setShippingCosts(CalculatedPrice $shippingCosts): void
    {
        $this->shippingCosts = $shippingCosts;
    }

    public function getApiAlias(): string
    {
        return 'cart_delivery';
    }
}
