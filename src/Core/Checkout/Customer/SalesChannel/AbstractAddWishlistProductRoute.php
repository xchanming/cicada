<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;

#[Package('checkout')]
abstract class AbstractAddWishlistProductRoute
{
    abstract public function getDecorated(): AbstractAddWishlistProductRoute;

    abstract public function add(string $productId, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse;
}
