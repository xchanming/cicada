<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppUrlChangeStrategyNotFoundHttpException extends CicadaHttpException
{
    public function __construct(AppUrlChangeStrategyNotFoundException $previous)
    {
        parent::__construct($previous->getMessage(), [], $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__APP_URL_CHANGE_RESOLVER_NOT_FOUND';
    }
}
