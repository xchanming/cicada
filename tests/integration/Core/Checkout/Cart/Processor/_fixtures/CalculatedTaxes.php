<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class CalculatedTaxes extends CalculatedTaxCollection
{
    /**
     * @param array<int, float> $taxes
     */
    public function __construct(array $taxes = [])
    {
        parent::__construct();
        foreach ($taxes as $rate => $value) {
            $this->add(new CalculatedTax($value, $rate, 0));
        }
    }
}
