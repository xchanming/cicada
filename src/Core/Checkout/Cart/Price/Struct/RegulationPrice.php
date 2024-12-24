<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Price\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Util\FloatComparator;

#[Package('checkout')]
class RegulationPrice extends Struct
{
    protected float $price;

    public function __construct(float $price)
    {
        $this->price = FloatComparator::cast($price);
    }

    public function getPrice(): float
    {
        return FloatComparator::cast($this->price);
    }

    public function getApiAlias(): string
    {
        return 'cart_regulation_price';
    }
}
