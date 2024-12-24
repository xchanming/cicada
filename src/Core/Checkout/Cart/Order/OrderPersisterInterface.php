<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Order;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
interface OrderPersisterInterface
{
    public function persist(Cart $cart, SalesChannelContext $context): string;
}
