<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('after-sales')]
class SalesChannelNotFoundException extends CicadaHttpException
{
    public function __construct(string $salesChannelId)
    {
        parent::__construct(
            'Sales channel with id "{{ salesChannelId }}" was not found.',
            ['salesChannelId' => $salesChannelId]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__SALES_CHANNEL_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
