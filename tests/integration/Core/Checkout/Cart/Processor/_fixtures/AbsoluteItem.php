<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CurrencyPriceDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @phpstan-ignore-next-line
 */
#[Package('checkout')]
class AbsoluteItem extends LineItem
{
    public function __construct(
        float $price,
        ?string $id = null
    ) {
        parent::__construct($id ?? Uuid::randomHex(), LineItem::DISCOUNT_LINE_ITEM);

        $this->priceDefinition = new CurrencyPriceDefinition(new PriceCollection([
            new Price(Defaults::CURRENCY, $price, $price, false),
        ]));
        $this->removable = true;
    }
}
