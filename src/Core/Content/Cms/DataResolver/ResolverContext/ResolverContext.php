<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataResolver\ResolverContext;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('buyers-experience')]
class ResolverContext
{
    public function __construct(
        private readonly SalesChannelContext $context,
        private readonly Request $request
    ) {
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
