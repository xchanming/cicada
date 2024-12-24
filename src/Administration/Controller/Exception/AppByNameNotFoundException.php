<?php declare(strict_types=1);

namespace Cicada\Administration\Controller\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('administration')]
class AppByNameNotFoundException extends CicadaHttpException
{
    public function __construct(string $appName)
    {
        parent::__construct(
            'The provided name {{ name }} is invalid and no app could be found.',
            ['name' => $appName]
        );
    }

    public function getErrorCode(): string
    {
        return 'ADMINISTRATION__APP_BY_NAME_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
