<?php
declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('customer-order')]
class PasswordPoliciesUpdatedException extends CustomerException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            'CHECKOUT__PASSWORD_POLICIES_UPDATED',
            'Password policies updated.'
        );
    }
}
