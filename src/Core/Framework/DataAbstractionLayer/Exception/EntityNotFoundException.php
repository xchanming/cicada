<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class EntityNotFoundException extends CicadaHttpException
{
    public function __construct(
        string $entity,
        string $identifier
    ) {
        parent::__construct(
            '{{ entity }} for id {{ identifier }} not found.',
            ['entity' => $entity, 'identifier' => $identifier]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__ENTITY_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
