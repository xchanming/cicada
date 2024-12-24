<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('core')]
class ContextLoadRouteResponse extends StoreApiResponse
{
    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(SalesChannelContext $object)
    {
        parent::__construct($object);
    }

    public function getContext(): SalesChannelContext
    {
        return $this->object;
    }
}
