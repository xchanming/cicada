<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CustomerNotFoundByHashException extends CustomerException
{
    public function __construct(string $hash)
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            self::CUSTOMER_NOT_FOUND_BY_HASH,
            'No matching customer for the hash "{{ hash }}" was found.',
            ['hash' => $hash]
        );
    }
}
