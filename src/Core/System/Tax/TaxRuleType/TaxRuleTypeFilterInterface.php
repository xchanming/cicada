<?php declare(strict_types=1);

namespace Cicada\Core\System\Tax\TaxRuleType;

use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;

#[Package('checkout')]
interface TaxRuleTypeFilterInterface
{
    public function match(TaxRuleEntity $taxRuleEntity, ?CustomerEntity $customer, ShippingLocation $shippingLocation): bool;
}
