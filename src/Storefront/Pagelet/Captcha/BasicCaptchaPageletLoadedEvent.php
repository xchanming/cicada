<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Captcha;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
class BasicCaptchaPageletLoadedEvent extends PageletLoadedEvent
{
    public function __construct(
        protected BasicCaptchaPagelet $pagelet,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        parent::__construct($salesChannelContext, $request);
    }

    public function getPagelet(): BasicCaptchaPagelet
    {
        return $this->pagelet;
    }
}
