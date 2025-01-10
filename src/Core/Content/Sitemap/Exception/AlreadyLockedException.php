<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('discovery')]
class AlreadyLockedException extends CicadaHttpException
{
    public function __construct(SalesChannelContext $salesChannelContext)
    {
        parent::__construct('Cannot acquire lock for sales channel {{salesChannelId}} and language {{languageId}}', [
            'salesChannelId' => $salesChannelContext->getSalesChannelId(),
            'languageId' => $salesChannelContext->getLanguageId(),
        ]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__SITEMAP_ALREADY_LOCKED';
    }
}
