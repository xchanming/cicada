<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;

/**
 * This route can be used to merge wishlist products from guest users to registered users.
 */
#[Package('checkout')]
abstract class AbstractMergeWishlistProductRoute
{
    abstract public function getDecorated(): AbstractMergeWishlistProductRoute;

    abstract public function merge(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse;
}
