<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class LogNotWritableException extends CicadaHttpException
{
    public function __construct(array $ids)
    {
        parent::__construct('Entity import_export_log is write-protected if not in system scope', ['ids' => $ids]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_LOG_NOT_WRITABLE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
