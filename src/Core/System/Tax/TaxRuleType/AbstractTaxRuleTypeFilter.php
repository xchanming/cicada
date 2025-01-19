<?php

declare(strict_types=1);

namespace Cicada\Core\System\Tax\TaxRuleType;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;

#[Package('checkout')]
abstract class AbstractTaxRuleTypeFilter implements TaxRuleTypeFilterInterface
{
    protected function isTaxActive(TaxRuleEntity $taxRuleEntity): bool
    {
        return $taxRuleEntity->getActiveFrom() < (new \DateTime())->setTimezone(new \DateTimeZone('Asia/Shanghai'));
    }
}
