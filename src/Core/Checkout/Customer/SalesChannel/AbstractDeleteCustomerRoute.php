<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route can be used to delete a customer
 */
#[Package('checkout')]
abstract class AbstractDeleteCustomerRoute
{
    abstract public function getDecorated(): AbstractDeleteCustomerRoute;

    abstract public function delete(SalesChannelContext $context, CustomerEntity $customer): NoContentResponse;
}
