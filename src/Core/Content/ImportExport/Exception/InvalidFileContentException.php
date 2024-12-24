<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use ImportExportException::invalidFileContent instead
 */
#[Package('services-settings')]
class InvalidFileContentException extends CicadaHttpException
{
    public function __construct(string $filename)
    {
        parent::__construct('The content of the file {{ filename }} is invalid.', ['filename' => $filename]);
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::invalidFileContent')
        );

        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::invalidFileContent')
        );

        return 'CONTENT__IMPORT_EXPORT_INVALID_FILE_CONTENT';
    }
}
