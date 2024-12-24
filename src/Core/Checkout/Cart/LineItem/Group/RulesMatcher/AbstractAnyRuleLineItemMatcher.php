<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group\RulesMatcher;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractAnyRuleLineItemMatcher
{
    abstract public function getDecorated(): AbstractAnyRuleLineItemMatcher;

    abstract public function isMatching(LineItemGroupDefinition $groupDefinition, LineItem $item, SalesChannelContext $context): bool;
}
