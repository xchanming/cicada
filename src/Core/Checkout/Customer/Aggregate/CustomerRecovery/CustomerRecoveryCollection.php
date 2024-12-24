<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<CustomerRecoveryEntity>
 */
#[Package('checkout')]
class CustomerRecoveryCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'customer_recovery_collection';
    }

    protected function getExpectedClass(): string
    {
        return CustomerRecoveryEntity::class;
    }
}
