<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;

/**
 * This route is used to send a password recovery mail
 * The required parameters are: "email" and "storefrontUrl"
 * The process can be completed with the hash in the Route Cicada\Core\Checkout\Customer\SalesChannel\AbstractResetPasswordRoute
 */
#[Package('checkout')]
abstract class AbstractSendPasswordRecoveryMailRoute
{
    abstract public function getDecorated(): AbstractSendPasswordRecoveryMailRoute;

    abstract public function sendRecoveryMail(RequestDataBag $data, SalesChannelContext $context, bool $validateStorefrontUrl = true): SuccessResponse;
}
