<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade;

use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsAddTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsCountTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsHasTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsIteratorTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsRemoveTrait;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * The ItemsFacade is a wrapper around a collection of line-items.
 *
 * @script-service cart_manipulation
 *
 * @implements \IteratorAggregate<array-key, ItemFacade>
 */
#[Package('checkout')]
class ItemsFacade implements \IteratorAggregate
{
    use ItemsAddTrait;
    use ItemsCountTrait;
    use ItemsHasTrait;
    use ItemsIteratorTrait;
    use ItemsRemoveTrait;

    /**
     * @internal
     */
    public function __construct(
        private LineItemCollection $items,
        private ScriptPriceStubs $priceStubs,
        private CartFacadeHelper $helper,
        private SalesChannelContext $context
    ) {
    }

    private function getItems(): LineItemCollection
    {
        return $this->items;
    }
}
