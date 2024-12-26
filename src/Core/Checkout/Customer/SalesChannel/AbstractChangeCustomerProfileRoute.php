<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;

/**
 * This route can be used to change profile information about the logged-in user
 * The required fields are "salutationId", "name"
 */
#[Package('checkout')]
abstract class AbstractChangeCustomerProfileRoute
{
    abstract public function getDecorated(): AbstractChangeCustomerProfileRoute;

    abstract public function change(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse;
}
