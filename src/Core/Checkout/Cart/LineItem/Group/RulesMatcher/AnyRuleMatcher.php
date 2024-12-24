<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group\RulesMatcher;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupRuleMatcherInterface;
use Cicada\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AnyRuleMatcher implements LineItemGroupRuleMatcherInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractAnyRuleLineItemMatcher $anyRuleProvider)
    {
    }

    public function getMatchingItems(
        LineItemGroupDefinition $groupDefinition,
        LineItemFlatCollection $items,
        SalesChannelContext $context
    ): LineItemFlatCollection {
        $matchingItems = [];

        foreach ($items as $item) {
            if ($this->anyRuleProvider->isMatching($groupDefinition, $item, $context)) {
                $matchingItems[] = $item;
            }
        }

        return new LineItemFlatCollection($matchingItems);
    }
}
