<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Aggregate\CustomerWishlist;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CustomerWishlistEntity>
 */
#[Package('discovery')]
class CustomerWishlistCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'customer_wishlist_collection';
    }

    protected function getExpectedClass(): string
    {
        return CustomerWishlistEntity::class;
    }
}
