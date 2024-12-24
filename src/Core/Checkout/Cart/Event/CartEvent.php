<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface CartEvent
{
    public function getCart(): Cart;
}
