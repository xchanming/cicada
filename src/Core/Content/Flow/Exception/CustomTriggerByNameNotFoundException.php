<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('services-settings')]
class CustomTriggerByNameNotFoundException extends CicadaHttpException
{
    public function __construct(string $eventName)
    {
        parent::__construct(
            'The provided event name {{ eventName }} is invalid or uninstalled and no custom trigger could be found.',
            ['eventName' => $eventName]
        );
    }

    public function getErrorCode(): string
    {
        return 'ADMINISTRATION__CUSTOM_TRIGGER_BY_NAME_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
