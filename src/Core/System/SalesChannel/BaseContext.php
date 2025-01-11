<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel;

use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\SalesChannel\Context\LanguageInfo;
use Cicada\Core\System\Tax\TaxCollection;

/**
 * @internal Use SalesChannelContext for extensions
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class BaseContext
{
    public function __construct(
        protected Context $context,
        protected SalesChannelEntity $salesChannel,
        protected CurrencyEntity $currency,
        protected CustomerGroupEntity $currentCustomerGroup,
        protected TaxCollection $taxRules,
        protected PaymentMethodEntity $paymentMethod,
        protected ShippingMethodEntity $shippingMethod,
        protected ShippingLocation $shippingLocation,
        private readonly CashRoundingConfig $itemRounding,
        private readonly CashRoundingConfig $totalRounding,
        private readonly LanguageInfo $languageInfo
    ) {
    }

    public function getCurrentCustomerGroup(): CustomerGroupEntity
    {
        return $this->currentCustomerGroup;
    }

    public function getCurrency(): CurrencyEntity
    {
        return $this->currency;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannel->getId();
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getTaxRules(): TaxCollection
    {
        return $this->taxRules;
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function getShippingMethod(): ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function getShippingLocation(): ShippingLocation
    {
        return $this->shippingLocation;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getTaxState(): string
    {
        return $this->context->getTaxState();
    }

    public function getTotalRounding(): CashRoundingConfig
    {
        return $this->totalRounding;
    }

    public function getItemRounding(): CashRoundingConfig
    {
        return $this->itemRounding;
    }

    public function getCurrencyId(): string
    {
        return $this->getCurrency()->getId();
    }

    public function getLanguageInfo(): LanguageInfo
    {
        return $this->languageInfo;
    }

    public function getApiAlias(): string
    {
        return 'base_channel_context';
    }
}
