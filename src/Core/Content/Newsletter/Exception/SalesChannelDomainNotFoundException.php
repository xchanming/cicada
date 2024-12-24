<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;

#[Package('buyers-experience')]
class SalesChannelDomainNotFoundException extends CicadaHttpException
{
    public function __construct(SalesChannelEntity $salesChannel)
    {
        parent::__construct(
            'No domain found for sales channel {{ salesChannel }}',
            ['salesChannel' => $salesChannel->getTranslation('name')]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__SALES_CHANNEL_DOMAIN_NOT_FOUND';
    }
}
