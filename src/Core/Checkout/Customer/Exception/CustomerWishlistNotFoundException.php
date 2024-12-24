<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CustomerWishlistNotFoundException extends CustomerException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            self::WISHLIST_NOT_FOUND,
            'Wishlist for this customer was not found.'
        );
    }
}
