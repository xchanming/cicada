<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Price\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;

#[Package('checkout')]
interface FilterableInterface
{
    public function getFilter(): ?Rule;
}
