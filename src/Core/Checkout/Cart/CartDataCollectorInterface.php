<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
interface CartDataCollectorInterface
{
    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void;
}
