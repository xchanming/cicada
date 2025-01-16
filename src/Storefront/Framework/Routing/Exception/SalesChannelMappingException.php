<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Routing\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('framework')]
class SalesChannelMappingException extends CicadaHttpException
{
    public function __construct(string $url)
    {
        parent::__construct(
            'Unable to find a matching sales channel for the request: "{{url}}". Please make sure the domain mapping is correct.',
            ['url' => $url]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_SALES_CHANNEL_MAPPING';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
