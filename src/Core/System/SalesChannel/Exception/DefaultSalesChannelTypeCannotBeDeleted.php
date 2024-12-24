<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class DefaultSalesChannelTypeCannotBeDeleted extends CicadaHttpException
{
    public function __construct(string $id)
    {
        parent::__construct('Cannot delete system default sales channel type', ['id' => $id]);
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM__SALES_CHANNEL_DEFAULT_TYPE_CANNOT_BE_DELETED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
