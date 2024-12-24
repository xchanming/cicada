<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class InvalidSortingDirectionException extends CicadaHttpException
{
    public function __construct(string $direction)
    {
        parent::__construct(
            'The given sort direction "{{ direction }}" is invalid.',
            ['direction' => $direction]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_SORT_DIRECTION';
    }
}
