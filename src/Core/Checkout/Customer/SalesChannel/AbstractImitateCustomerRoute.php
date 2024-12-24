<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\ContextTokenResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractImitateCustomerRoute
{
    abstract public function getDecorated(): AbstractImitateCustomerRoute;

    abstract public function imitateCustomerLogin(RequestDataBag $data, SalesChannelContext $context): ContextTokenResponse;
}
