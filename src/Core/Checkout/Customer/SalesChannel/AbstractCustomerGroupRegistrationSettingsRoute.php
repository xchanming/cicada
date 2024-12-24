<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractCustomerGroupRegistrationSettingsRoute
{
    abstract public function getDecorated(): AbstractCustomerGroupRegistrationSettingsRoute;

    abstract public function load(string $customerGroupId, SalesChannelContext $context): CustomerGroupRegistrationSettingsRouteResponse;
}
