<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Increment\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class IncrementGatewayNotFoundException extends CicadaHttpException
{
    public function __construct(string $pool)
    {
        parent::__construct(
            'Increment gateway for pool "{{ pool }}" was not found.',
            ['pool' => $pool]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INCREMENT_GATEWAY_NOT_FOUND';
    }
}
