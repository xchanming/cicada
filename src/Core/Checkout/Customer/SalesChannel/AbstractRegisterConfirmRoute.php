<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route can be used to complete the double optin registration.
 * The required parameters are: "hash" (received from the mail) and "em" (received from the mail)
 */
#[Package('checkout')]
abstract class AbstractRegisterConfirmRoute
{
    abstract public function getDecorated(): AbstractRegisterConfirmRoute;

    abstract public function confirm(RequestDataBag $dataBag, SalesChannelContext $context): CustomerResponse;
}
