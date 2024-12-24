<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Service;

use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Content\ProductExport\Struct\ProductExportResult;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductExportGeneratorInterface
{
    public function generate(
        ProductExportEntity $productExport,
        ExportBehavior $exportBehavior
    ): ?ProductExportResult;
}
