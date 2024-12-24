<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CustomerNotFoundException extends CustomerException
{
    public function __construct(string $email)
    {
        parent::__construct(
            Response::HTTP_UNAUTHORIZED,
            self::CUSTOMER_NOT_FOUND,
            'No matching customer for the email "{{ email }}" was found.',
            ['email' => $email]
        );
    }
}
