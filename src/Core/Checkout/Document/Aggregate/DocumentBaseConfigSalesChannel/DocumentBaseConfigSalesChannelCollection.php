<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<DocumentBaseConfigSalesChannelEntity>
 */
#[Package('checkout')]
class DocumentBaseConfigSalesChannelCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'document_base_config_sales_channel_collection';
    }

    protected function getExpectedClass(): string
    {
        return DocumentBaseConfigSalesChannelEntity::class;
    }
}
