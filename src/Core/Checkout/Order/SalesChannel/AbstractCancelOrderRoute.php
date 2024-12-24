<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used to cancel a order
 */
#[Package('checkout')]
abstract class AbstractCancelOrderRoute
{
    abstract public function getDecorated(): AbstractCancelOrderRoute;

    abstract public function cancel(Request $request, SalesChannelContext $context): CancelOrderRouteResponse;
}
