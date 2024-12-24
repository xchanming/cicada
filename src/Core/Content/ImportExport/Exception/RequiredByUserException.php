<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class RequiredByUserException extends CicadaHttpException
{
    public function __construct(string $column)
    {
        parent::__construct('{{ column }} is set to required by the user but has no value', [
            'column' => $column,
        ]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_REQUIRED_BY_USER';
    }
}
