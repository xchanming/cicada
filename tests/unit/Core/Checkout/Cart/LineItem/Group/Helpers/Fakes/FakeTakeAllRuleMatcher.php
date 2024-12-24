<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Fakes;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupRuleMatcherInterface;
use Cicada\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
class FakeTakeAllRuleMatcher implements LineItemGroupRuleMatcherInterface
{
    private int $sequenceCount = 0;

    public function __construct(private readonly FakeSequenceSupervisor $sequenceSupervisor)
    {
    }

    public function getSequenceCount(): int
    {
        return $this->sequenceCount;
    }

    public function getMatchingItems(LineItemGroupDefinition $groupDefinition, LineItemFlatCollection $items, SalesChannelContext $context): LineItemFlatCollection
    {
        $this->sequenceCount = $this->sequenceSupervisor->getNextCount();

        return $items;
    }
}
