<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;

/**
 * This route is used to change the default payment method of a logged-in user
 *
 * @deprecated tag:v6.7.0 - will be removed, customer has no default payment method anymore
 */
#[Package('checkout')]
abstract class AbstractChangePaymentMethodRoute
{
    abstract public function getDecorated(): AbstractChangePaymentMethodRoute;

    abstract public function change(string $paymentMethodId, RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse;
}
