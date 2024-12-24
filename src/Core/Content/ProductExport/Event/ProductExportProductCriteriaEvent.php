<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Event;

use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
class ProductExportProductCriteriaEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    public function __construct(
        protected Criteria $criteria,
        protected ProductExportEntity $productExport,
        protected ExportBehavior $exportBehaviour,
        protected SalesChannelContext $salesChannelContext
    ) {
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getProductExport(): ProductExportEntity
    {
        return $this->productExport;
    }

    public function getExportBehaviour(): ExportBehavior
    {
        return $this->exportBehaviour;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
