<?php declare(strict_types=1);

namespace Cicada\Storefront\Event;

use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('storefront')]
class SwitchBuyBoxVariantEvent extends Event implements CicadaSalesChannelEvent
{
    public function __construct(
        private readonly string $elementId,
        private readonly SalesChannelProductEntity $product,
        private readonly ?PropertyGroupCollection $configurator,
        private readonly Request $request,
        private readonly SalesChannelContext $salesChannelContext
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function getProduct(): SalesChannelProductEntity
    {
        return $this->product;
    }

    public function getConfigurator(): ?PropertyGroupCollection
    {
        return $this->configurator;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }
}
