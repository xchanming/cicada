<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Validator;

use Cicada\Core\Content\ProductExport\Error\ErrorCollection;
use Cicada\Core\Content\ProductExport\Error\XmlValidationError;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class XmlValidator implements ValidatorInterface
{
    public function validate(ProductExportEntity $productExportEntity, string $productExportContent, ErrorCollection $errors): void
    {
        if ($productExportEntity->getFileFormat() !== ProductExportEntity::FILE_FORMAT_XML) {
            return;
        }

        $backup_errors = libxml_use_internal_errors(true);

        if (simplexml_load_string($productExportContent) === false) {
            $errors->add(new XmlValidationError($productExportEntity->getId(), libxml_get_errors()));
        }

        libxml_use_internal_errors($backup_errors);
    }
}
