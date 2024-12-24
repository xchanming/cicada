<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Exception;

use Cicada\Core\Content\ProductExport\Error\Error;
use Cicada\Core\Content\ProductExport\Error\ErrorMessage;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ExportInvalidException extends CicadaHttpException
{
    /**
     * @var ErrorMessage[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $errorMessages;

    /**
     * @param Error[] $errors
     */
    public function __construct(
        ProductExportEntity $productExportEntity,
        array $errors
    ) {
        $errorMessages = array_merge(
            ...array_map(
                fn (Error $error) => $error->getErrorMessages(),
                $errors
            )
        );

        $this->errorMessages = $errorMessages;

        parent::__construct(
            \sprintf(
                'Export file generation for product export %s (%s) resulted in validation errors',
                $productExportEntity->getId(),
                $productExportEntity->getFileName()
            ),
            ['errors' => $errors, 'errorMessages' => $errorMessages]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_EXPORT_INVALID_CONTENT';
    }

    /**
     * @return ErrorMessage[]
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
