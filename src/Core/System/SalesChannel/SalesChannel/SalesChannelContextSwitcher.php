<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
class SalesChannelContextSwitcher
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractContextSwitchRoute $contextSwitchRoute)
    {
    }

    public function update(DataBag $data, SalesChannelContext $context): void
    {
        $this->contextSwitchRoute->switchContext($data->toRequestDataBag(), $context);
    }
}
