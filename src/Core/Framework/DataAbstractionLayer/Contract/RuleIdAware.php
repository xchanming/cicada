<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Contract;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
interface RuleIdAware
{
    public function getAvailabilityRuleId(): ?string;
}
