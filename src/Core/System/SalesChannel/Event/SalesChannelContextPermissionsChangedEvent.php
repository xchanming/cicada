<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
class SalesChannelContextPermissionsChangedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var array
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $permissions = [];

    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        array $permissions
    ) {
        $this->permissions = $permissions;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
