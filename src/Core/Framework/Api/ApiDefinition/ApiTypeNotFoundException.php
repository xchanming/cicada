<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\ApiDefinition;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ApiTypeNotFoundException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        parent::__construct(
            'A api type "{{ type }}" was not found.',
            ['type' => $type]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__API_DEFINITION_TYPE_NOT_SUPPORTED';
    }
}
