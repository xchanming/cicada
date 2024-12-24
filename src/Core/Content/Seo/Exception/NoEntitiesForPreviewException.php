<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class NoEntitiesForPreviewException extends CicadaHttpException
{
    final public const ERROR_CODE = 'FRAMEWORK__NO_ENTRIES_FOR_SEO_URL_PREVIEW';

    public function __construct(
        string $entityName,
        string $routeName
    ) {
        parent::__construct(
            'No entites of type {{ entityName }} could be found to create a preview for route {{ routeName }}',
            ['entityName' => $entityName, 'routeName' => $routeName]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }
}
