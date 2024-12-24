<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
interface LineItemGroupRuleMatcherInterface
{
    /**
     * Gets a list of line items that match for the provided group object.
     * You can use AND conditions, OR conditions, or anything else, depending on your implementation.
     */
    public function getMatchingItems(LineItemGroupDefinition $groupDefinition, LineItemFlatCollection $items, SalesChannelContext $context): LineItemFlatCollection;
}
