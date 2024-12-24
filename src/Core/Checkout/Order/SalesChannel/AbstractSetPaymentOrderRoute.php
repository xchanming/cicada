<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used to update the paymentMethod for an order
 */
#[Package('checkout')]
abstract class AbstractSetPaymentOrderRoute
{
    abstract public function getDecorated(): AbstractSetPaymentOrderRoute;

    abstract public function setPayment(Request $request, SalesChannelContext $context): SetPaymentOrderRouteResponse;
}
