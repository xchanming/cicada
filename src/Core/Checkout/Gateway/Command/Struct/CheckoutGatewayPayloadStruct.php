<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Struct;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class CheckoutGatewayPayloadStruct extends Struct
{
    /**
     * @internal
     */
    public function __construct(
        protected Cart $cart,
        protected SalesChannelContext $salesChannelContext,
        protected PaymentMethodCollection $paymentMethods,
        protected ShippingMethodCollection $shippingMethods,
    ) {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getPaymentMethods(): PaymentMethodCollection
    {
        return $this->paymentMethods;
    }

    public function getShippingMethods(): ShippingMethodCollection
    {
        return $this->shippingMethods;
    }
}
