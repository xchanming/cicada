<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class InvalidIdentifierException extends CicadaHttpException
{
    public function __construct(string $fieldName)
    {
        parent::__construct('The identifier of {{ fieldName }} should not contain pipe character.', ['fieldName' => $fieldName]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_INVALID_IDENTIFIER';
    }
}
