<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Storefront\Page\Page;

#[Package('framework')]
class CheckoutCartPage extends Page
{
    /**
     * @var Cart
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cart;

    /**
     * @var CountryCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $countries;

    /**
     * @var PaymentMethodCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethods;

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

    public function setCountries(CountryCollection $countries): void
    {
        $this->countries = $countries;
    }

    public function getCountries(): CountryCollection
    {
        return $this->countries;
    }

    public function setPaymentMethods(PaymentMethodCollection $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function getPaymentMethods(): PaymentMethodCollection
    {
        return $this->paymentMethods;
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
