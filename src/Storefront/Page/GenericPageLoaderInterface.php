<?php declare(strict_types=1);

namespace Cicada\Storefront\Page;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
interface GenericPageLoaderInterface
{
    public function load(Request $request, SalesChannelContext $context): Page;
}
