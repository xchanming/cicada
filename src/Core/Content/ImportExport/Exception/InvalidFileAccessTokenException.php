<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class InvalidFileAccessTokenException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Access to file denied due to invalid access token');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_FILE_INVALID_ACCESS_TOKEN';
    }
}
