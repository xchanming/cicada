<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\Price\CashRounding;
use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Tax\TaxCalculator;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
class CalculatedItem extends QuantityItem
{
    public function __construct(
        float $price,
        TaxRuleCollection $taxes,
        SalesChannelContext $context,
        bool $good = true,
        int $quantity = 1
    ) {
        parent::__construct($price, $taxes, $good, $quantity);

        $calculator = new QuantityPriceCalculator(
            new GrossPriceCalculator(new TaxCalculator(), new CashRounding()),
            new NetPriceCalculator(new TaxCalculator(), new CashRounding())
        );

        \assert($this->getPriceDefinition() instanceof QuantityPriceDefinition);
        $this->price = $calculator->calculate($this->getPriceDefinition(), $context);
    }
}
