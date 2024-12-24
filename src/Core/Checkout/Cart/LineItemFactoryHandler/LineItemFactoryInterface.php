<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItemFactoryHandler;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * A LineItemFactory is, as the name suggests, responsible for creating a LineItem for the shopping cart.
 * Even if the Cart\LineItem is kept abstract, some LineItems need additional data to be created.
 * Since this is knowledge from the LineItem's processor, it should not be necessary to know this knowledge as a user of the cart.
 */
#[Package('checkout')]
interface LineItemFactoryInterface
{
    public function supports(string $type): bool;

    public function create(array $data, SalesChannelContext $context): LineItem;

    public function update(LineItem $lineItem, array $data, SalesChannelContext $context): void;
}
