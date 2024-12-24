<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade;

use Cicada\Core\Checkout\Cart\Facade\Traits\DiscountTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsCountTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsGetTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsHasTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsIteratorTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\ItemsRemoveTrait;
use Cicada\Core\Checkout\Cart\Facade\Traits\SurchargeTrait;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * The ContainerFacade allows you to wrap multiple line-items inside a container line-item.
 *
 * @script-service cart_manipulation
 *
 * @final
 */
#[Package('checkout')]
class ContainerFacade extends ItemFacade
{
    use DiscountTrait;
    use ItemsCountTrait;
    use ItemsGetTrait;
    use ItemsHasTrait;
    use ItemsIteratorTrait;
    use ItemsRemoveTrait;
    use SurchargeTrait;

    private LineItem $item;

    private ScriptPriceStubs $priceStubs;

    /**
     * @internal
     */
    public function __construct(
        LineItem $item,
        ScriptPriceStubs $priceStubs,
        CartFacadeHelper $helper,
        SalesChannelContext $context
    ) {
        parent::__construct($item, $priceStubs, $helper, $context);

        $this->item = $item;
        $this->helper = $helper;
        $this->priceStubs = $priceStubs;
        $this->context = $context;
    }

    /**
     * The `product()` method returns all products inside the current container for further manipulation.
     * Similar to the `children()` method, but the line-items are filtered, to only contain product line items.
     *
     * @return ProductsFacade A `ProductsFacade` containing all product line-items inside the current container as a collection.
     */
    public function products(): ProductsFacade
    {
        return new ProductsFacade($this->item->getChildren(), $this->priceStubs, $this->helper, $this->context);
    }

    /**
     * Use the `add()` method to add an item to this container.
     *
     * @param ItemFacade $item The item that should be added.
     *
     * @return ItemFacade The item that was added to the container.
     *
     * @example add-container/add-container.twig 12 1 Add a product to the container and reduce the quantity of the original line-item.
     */
    public function add(ItemFacade $item): ItemFacade
    {
        $this->item->getChildren()->add($item->getItem());

        /** @var ItemFacade $item */
        $item = $this->get($item->getId());

        return $item;
    }

    protected function getItems(): LineItemCollection
    {
        // switch items pointer to children. Used for Items*Traits and DiscountTrait
        return $this->item->getChildren();
    }
}
