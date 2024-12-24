<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('core')]
interface SalesChannelContextServiceInterface
{
    public function get(SalesChannelContextServiceParameters $parameters): SalesChannelContext;
}
