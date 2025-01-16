<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\CustomerGroupRegistration;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loads the customer group registration page
 */
#[Package('framework')]
abstract class AbstractCustomerGroupRegistrationPageLoader
{
    abstract public function getDecorated(): AbstractCustomerGroupRegistrationPageLoader;

    abstract public function load(Request $request, SalesChannelContext $salesChannelContext): CustomerGroupRegistrationPage;
}
