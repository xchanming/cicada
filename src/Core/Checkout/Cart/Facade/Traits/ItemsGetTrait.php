<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade\Traits;

use Cicada\Core\Checkout\Cart\Facade\CartFacadeHelper;
use Cicada\Core\Checkout\Cart\Facade\ContainerFacade;
use Cicada\Core\Checkout\Cart\Facade\ItemFacade;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
trait ItemsGetTrait
{
    private LineItemCollection $items;

    private CartFacadeHelper $helper;

    private SalesChannelContext $context;

    private ScriptPriceStubs $priceStubs;

    /**
     * `get()` returns the line-item with the given id from this collection.
     *
     * @param string $id The id of the line-item that should be returned.
     *
     * @return ItemFacade|null The line-item with the given id, or null if it does not exist.
     */
    public function get(string $id): ?ItemFacade
    {
        $item = $this->getItems()->get($id);

        if (!$item instanceof LineItem) {
            return null;
        }

        return match ($item->getType()) {
            LineItem::CONTAINER_LINE_ITEM => new ContainerFacade($item, $this->priceStubs, $this->helper, $this->context),
            default => new ItemFacade($item, $this->priceStubs, $this->helper, $this->context),
        };
    }

    private function getItems(): LineItemCollection
    {
        return $this->items;
    }
}
