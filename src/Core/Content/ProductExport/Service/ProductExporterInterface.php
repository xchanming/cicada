<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Service;

use Cicada\Core\Content\ProductExport\Exception\ExportInvalidException;
use Cicada\Core\Content\ProductExport\Exception\ExportNotFoundException;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
interface ProductExporterInterface
{
    /**
     * @throws ExportInvalidException
     * @throws ExportNotFoundException
     */
    public function export(
        SalesChannelContext $context,
        ExportBehavior $behavior,
        ?string $productExportId = null
    ): void;
}
