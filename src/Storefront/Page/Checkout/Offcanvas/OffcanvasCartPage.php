<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Offcanvas;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('framework')]
class OffcanvasCartPage extends Page
{
    /**
     * @var Cart
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cart;

    /**
     * @var ShippingMethodCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingMethods;

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function setShippingMethods(ShippingMethodCollection $shippingMethods): void
    {
        $this->shippingMethods = $shippingMethods;
    }

    public function getShippingMethods(): ShippingMethodCollection
    {
        return $this->shippingMethods;
    }
}
