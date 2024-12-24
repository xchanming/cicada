<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class LowTaxes extends TaxRuleCollection
{
    public function __construct()
    {
        parent::__construct([new TaxRule(7)]);
    }
}
