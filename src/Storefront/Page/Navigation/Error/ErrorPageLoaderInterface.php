<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Navigation\Error;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
interface ErrorPageLoaderInterface
{
    public function load(string $cmsErrorLayoutId, Request $request, SalesChannelContext $context): ErrorPage;
}
