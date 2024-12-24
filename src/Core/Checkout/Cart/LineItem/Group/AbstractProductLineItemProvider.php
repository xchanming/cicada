<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
abstract class AbstractProductLineItemProvider
{
    abstract public function getDecorated(): AbstractProductLineItemProvider;

    abstract public function getProducts(Cart $cart): LineItemCollection;
}
