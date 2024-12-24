<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Aggregate\SalesChannelType;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;

/**
 * @extends EntityCollection<SalesChannelTypeEntity>
 */
#[Package('buyers-experience')]
class SalesChannelTypeCollection extends EntityCollection
{
    public function getSalesChannels(): SalesChannelCollection
    {
        return new SalesChannelCollection(
            $this->fmap(fn (SalesChannelTypeEntity $salesChannel) => $salesChannel->getSalesChannels())
        );
    }

    public function getApiAlias(): string
    {
        return 'sales_channel_type_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalesChannelTypeEntity::class;
    }
}
