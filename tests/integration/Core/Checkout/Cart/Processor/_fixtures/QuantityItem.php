<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @phpstan-ignore-next-line
 */
#[Package('checkout')]
class QuantityItem extends LineItem
{
    public function __construct(
        float $price,
        TaxRuleCollection $taxes,
        bool $good = true,
        int $quantity = 1
    ) {
        parent::__construct(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, null, $quantity);

        $this->priceDefinition = new QuantityPriceDefinition($price, $taxes, $quantity);
        $this->setGood($good);
    }
}
