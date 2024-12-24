<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class FileNotFoundException extends CicadaHttpException
{
    public function __construct(string $fileId)
    {
        parent::__construct('Cannot find import/export file with id {{ fileId }}', ['fileId' => $fileId]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_FILE_NOT_FOUND';
    }
}
