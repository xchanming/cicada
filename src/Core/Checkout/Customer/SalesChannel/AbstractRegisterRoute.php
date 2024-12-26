<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route is used for customer registration
 * The required parameters are: "salutationId", "name", "email", "password", "billingAddress" and "storefrontUrl"
 * The "billingAddress" should has required parameters: "salutationId", "name",  "street", "zipcode", "city", "countyId".
 */
#[Package('checkout')]
abstract class AbstractRegisterRoute
{
    abstract public function getDecorated(): AbstractRegisterRoute;

    abstract public function register(RequestDataBag $data, SalesChannelContext $context, bool $validateStorefrontUrl = true, ?DataValidationDefinition $additionalValidationDefinitions = null): CustomerResponse;
}
