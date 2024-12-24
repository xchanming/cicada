<?php declare(strict_types=1);

namespace Cicada\Storefront\Page;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Storefront\Pagelet\Footer\FooterPagelet;
use Cicada\Storefront\Pagelet\Header\HeaderPagelet;

#[Package('storefront')]
class Page extends Struct
{
    /**
     * @var HeaderPagelet|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $header;

    /**
     * @var FooterPagelet|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $footer;

    /**
     * @var ShippingMethodCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelShippingMethods;

    /**
     * @var PaymentMethodCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelPaymentMethods;

    /**
     * @var MetaInformation
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $metaInformation;

    public function getHeader(): ?HeaderPagelet
    {
        return $this->header;
    }

    public function setHeader(?HeaderPagelet $header): void
    {
        $this->header = $header;
    }

    public function getFooter(): ?FooterPagelet
    {
        return $this->footer;
    }

    public function setFooter(?FooterPagelet $footer): void
    {
        $this->footer = $footer;
    }

    public function getSalesChannelShippingMethods(): ?ShippingMethodCollection
    {
        return $this->salesChannelShippingMethods;
    }

    public function setSalesChannelShippingMethods(ShippingMethodCollection $salesChannelShippingMethods): void
    {
        $this->salesChannelShippingMethods = $salesChannelShippingMethods;
    }

    public function getSalesChannelPaymentMethods(): ?PaymentMethodCollection
    {
        return $this->salesChannelPaymentMethods;
    }

    public function setSalesChannelPaymentMethods(PaymentMethodCollection $salesChannelPaymentMethods): void
    {
        $this->salesChannelPaymentMethods = $salesChannelPaymentMethods;
    }

    public function getMetaInformation(): ?MetaInformation
    {
        return $this->metaInformation;
    }

    public function setMetaInformation(MetaInformation $metaInformation): void
    {
        $this->metaInformation = $metaInformation;
    }
}
