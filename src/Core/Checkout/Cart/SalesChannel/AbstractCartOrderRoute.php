<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route can be used to create an order from the cart
 */
#[Package('checkout')]
abstract class AbstractCartOrderRoute
{
    abstract public function getDecorated(): AbstractCartOrderRoute;

    abstract public function order(Cart $cart, SalesChannelContext $context, RequestDataBag $data): CartOrderRouteResponse;
}
