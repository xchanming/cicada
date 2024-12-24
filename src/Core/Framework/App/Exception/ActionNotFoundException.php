<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - will be removed, use AppException::actionNotFound() instead
 *
 * @internal only for use by the app-system
 */
#[Package('core')]
class ActionNotFoundException extends CicadaHttpException
{
    public function __construct()
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'AppException::actionNotFound')
        );

        parent::__construct('The requested app action does not exist');
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'AppException::actionNotFound')
        );

        return 'FRAMEWORK__APP_ACTION_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'AppException::actionNotFound')
        );

        return Response::HTTP_NOT_FOUND;
    }
}
