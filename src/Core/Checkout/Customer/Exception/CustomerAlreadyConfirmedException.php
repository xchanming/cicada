<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CustomerAlreadyConfirmedException extends CustomerException
{
    public function __construct(string $id)
    {
        parent::__construct(
            Response::HTTP_PRECONDITION_FAILED,
            self::CUSTOMER_IS_ALREADY_CONFIRMED,
            'The customer with the id "{{ customerId }}" is already confirmed.',
            ['customerId' => $id]
        );
    }
}
