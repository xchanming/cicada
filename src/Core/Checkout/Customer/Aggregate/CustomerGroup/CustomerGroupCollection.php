<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CustomerGroupEntity>
 */
#[Package('discovery')]
class CustomerGroupCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'customer_group_collection';
    }

    protected function getExpectedClass(): string
    {
        return CustomerGroupEntity::class;
    }
}
