<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Exception;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class DuplicateWishlistProductException extends CustomerException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            self::DUPLICATE_WISHLIST_PRODUCT,
            'Product already added in wishlist'
        );
    }
}
