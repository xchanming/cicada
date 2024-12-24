<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class SeoUrlRouteNotFoundException extends CicadaHttpException
{
    final public const ERROR_CODE = 'FRAMEWORK__SEO_URL_ROUTE_NOT_FOUND';

    public function __construct(string $routeName)
    {
        parent::__construct('seo url route"{{ routeName }}" not found.', ['routeName' => $routeName]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }
}
