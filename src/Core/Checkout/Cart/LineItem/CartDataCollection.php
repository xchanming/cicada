<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<mixed>
 */
#[Package('checkout')]
class CartDataCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'cart_data_collection';
    }
}
