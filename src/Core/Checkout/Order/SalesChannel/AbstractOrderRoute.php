<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Order\Exception\GuestNotAuthenticatedException;
use Cicada\Core\Checkout\Order\Exception\WrongGuestCredentialsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route is used to load the orders of the logged-in customer
 * With this route it is also possible to send the standard API parameters such as: 'page', 'limit', 'filter', etc.
 */
#[Package('checkout')]
abstract class AbstractOrderRoute
{
    abstract public function getDecorated(): AbstractOrderRoute;

    /**
     * @throws CustomerNotLoggedInException
     * @throws GuestNotAuthenticatedException
     * @throws WrongGuestCredentialsException
     */
    abstract public function load(Request $request, SalesChannelContext $context, Criteria $criteria): OrderRouteResponse;
}
