<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class InvalidLimitQueryException extends CicadaHttpException
{
    public function __construct(mixed $limit)
    {
        parent::__construct(
            'The limit parameter must be a positive integer greater or equals than 1. Given: {{ limit }}',
            ['limit' => $limit]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_LIMIT_QUERY';
    }
}
