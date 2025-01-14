<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SalesChannelDomainEntity>
 */
#[Package('discovery')]
class SalesChannelDomainCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'sales_channel_domain_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalesChannelDomainEntity::class;
    }
}
