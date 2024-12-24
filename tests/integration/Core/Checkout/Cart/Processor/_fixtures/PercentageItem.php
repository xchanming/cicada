<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @phpstan-ignore-next-line
 */
#[Package('checkout')]
class PercentageItem extends LineItem
{
    public function __construct(
        int $percentage,
        ?string $id = null
    ) {
        parent::__construct($id ?? Uuid::randomHex(), LineItem::DISCOUNT_LINE_ITEM);

        $this->priceDefinition = new PercentagePriceDefinition($percentage);
        $this->removable = true;
    }
}
