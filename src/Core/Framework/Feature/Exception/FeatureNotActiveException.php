<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Feature\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('framework')]
class FeatureNotActiveException extends CicadaHttpException
{
    public function __construct(
        string $feature,
        ?\Throwable $previous = null
    ) {
        $message = \sprintf('This function can only be used with feature flag %s', $feature);
        parent::__construct($message, [], $previous);
    }

    public function getErrorCode(): string
    {
        return 'FEATURE_NOT_ACTIVE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
