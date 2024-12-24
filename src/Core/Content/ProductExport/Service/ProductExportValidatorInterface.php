<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Service;

use Cicada\Core\Content\ProductExport\Error\Error;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductExportValidatorInterface
{
    /**
     * @return list<Error>
     */
    public function validate(ProductExportEntity $productExportEntity, string $productExportContent): array;
}
