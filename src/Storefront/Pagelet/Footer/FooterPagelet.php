<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Footer;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Pagelet\NavigationPagelet;

/**
 * @codeCoverageIgnore
 */
#[Package('framework')]
class FooterPagelet extends NavigationPagelet
{
    /**
     * @deprecated tag:v6.7.0 - reason:becomes-internal - Constructor will be internal with v6.7.0
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter serviceMenu will be required
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter paymentMethods will be required
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter shippingMethods will be required
     */
    public function __construct(
        ?Tree $navigation,
        protected ?CategoryCollection $serviceMenu = null,
        protected ?PaymentMethodCollection $paymentMethods = null,
        protected ?ShippingMethodCollection $shippingMethods = null,
    ) {
        parent::__construct($navigation);
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - Will only return CategoryCollection
     */
    public function getServiceMenu(): ?CategoryCollection
    {
        return $this->serviceMenu;
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - Will only return PaymentMethodCollection
     */
    public function getPaymentMethods(): ?PaymentMethodCollection
    {
        return $this->paymentMethods;
    }

    /**
     * @deprecated tag:v6.7.0 - reason:return-type-change - Will only return ShippingMethodCollection
     */
    public function getShippingMethods(): ?ShippingMethodCollection
    {
        return $this->shippingMethods;
    }
}
