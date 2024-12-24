<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use ImportExportException::fileNotReadable instead
 */
#[Package('services-settings')]
class FileNotReadableException extends CicadaHttpException
{
    public function __construct(string $path)
    {
        parent::__construct('Import file is not readable at {{ path }}.', ['path' => $path]);
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::fileNotReadable')
        );

        return 'CONTENT__IMPORT_FILE_IS_NOT_READABLE';
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'ImportExportException::fileNotReadable')
        );

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
