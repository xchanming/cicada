<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade\Traits;

use Cicada\Core\Checkout\Cart\Facade\CartFacadeHelper;
use Cicada\Core\Checkout\Cart\Facade\ContainerFacade;
use Cicada\Core\Checkout\Cart\Facade\ItemFacade;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
trait ItemsIteratorTrait
{
    private CartFacadeHelper $helper;

    private LineItemCollection $items;

    private SalesChannelContext $context;

    /**
     * @internal should not be used directly, loop over an ItemsFacade directly inside twig instead
     *
     * @return \ArrayIterator<array-key, ItemFacade|ContainerFacade>
     */
    public function getIterator(): \ArrayIterator
    {
        $items = [];
        foreach ($this->getItems() as $key => $item) {
            $items[$key] = match ($item->getType()) {
                LineItem::CONTAINER_LINE_ITEM => new ContainerFacade($item, $this->priceStubs, $this->helper, $this->context),
                default => new ItemFacade($item, $this->priceStubs, $this->helper, $this->context),
            };
        }

        return new \ArrayIterator($items);
    }

    private function getItems(): LineItemCollection
    {
        return $this->items;
    }
}
