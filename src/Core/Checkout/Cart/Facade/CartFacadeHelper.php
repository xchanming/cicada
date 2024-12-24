<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryRegistry;
use Cicada\Core\Checkout\Cart\Processor;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
class CartFacadeHelper
{
    /**
     * @internal
     */
    public function __construct(
        private readonly LineItemFactoryRegistry $factory,
        private readonly Processor $processor
    ) {
    }

    public function product(string $productId, int $quantity, SalesChannelContext $context): LineItem
    {
        $data = [
            'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            'id' => $productId,
            'referencedId' => $productId,
            'quantity' => $quantity,
        ];

        return $this->factory->create($data, $context);
    }

    public function calculate(Cart $cart, CartBehavior $behavior, SalesChannelContext $context): Cart
    {
        return $this->processor->process($cart, $context, $behavior);
    }
}
