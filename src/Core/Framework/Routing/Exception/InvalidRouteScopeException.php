<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class InvalidRouteScopeException extends CicadaHttpException
{
    public function __construct(string $routeName)
    {
        parent::__construct(
            'Invalid route scope for route {{ routeName }}.',
            ['routeName' => $routeName]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__ROUTING_INVALID_ROUTE_SCOPE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_PRECONDITION_FAILED;
    }
}
