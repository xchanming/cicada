<?php declare(strict_types=1);

namespace Cicada\Core\System\CustomEntity\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - use CustomEntityException::notFound instead - reason:remove-exception
 */
#[Package('core')]
class CustomEntityNotFoundException extends CicadaHttpException
{
    public function __construct(string $customEntity)
    {
        parent::__construct(
            'Custom Entity "{{ entityName }}" does not exist.',
            ['entityName' => $customEntity]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__CUSTOM_ENTITY_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
