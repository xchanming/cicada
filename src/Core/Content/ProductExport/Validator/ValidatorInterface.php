<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Validator;

use Cicada\Core\Content\ProductExport\Error\ErrorCollection;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ValidatorInterface
{
    public function validate(ProductExportEntity $productExportEntity, string $productExportContent, ErrorCollection $errors): void;
}
