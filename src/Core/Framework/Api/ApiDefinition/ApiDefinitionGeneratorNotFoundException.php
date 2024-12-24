<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\ApiDefinition;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ApiDefinitionGeneratorNotFoundException extends CicadaHttpException
{
    public function __construct(string $format)
    {
        parent::__construct(
            'A definition generator for format "{{ format }}" was not found.',
            ['format' => $format]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__API_DEFINITION_GENERATOR_NOT_SUPPORTED';
    }
}
