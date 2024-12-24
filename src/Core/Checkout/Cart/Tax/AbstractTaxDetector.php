<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Tax;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractTaxDetector
{
    abstract public function getDecorated(): AbstractTaxDetector;

    abstract public function useGross(SalesChannelContext $context): bool;

    abstract public function isNetDelivery(SalesChannelContext $context): bool;

    abstract public function getTaxState(SalesChannelContext $context): string;

    abstract public function isCompanyTaxFree(SalesChannelContext $context, CountryEntity $shippingLocationCountry): bool;
}
