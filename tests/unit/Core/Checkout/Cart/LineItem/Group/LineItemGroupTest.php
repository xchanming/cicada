<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroup;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LineItemGroup::class)]
class LineItemGroupTest extends TestCase
{
    /**
     * This test verifies that we have an empty
     * list on new instances and not null.
     */
    #[Group('lineitemgroup')]
    public function testItemsAreEmptyOnNewGroup(): void
    {
        $group = new LineItemGroup();

        static::assertCount(0, $group->getItems());
    }

    /**
     * This test verifies that our hasItems
     * function works correctly for empty entries.
     */
    #[Group('lineitemgroup')]
    public function testHasItemsOnEmptyList(): void
    {
        $group = new LineItemGroup();

        static::assertFalse($group->hasItems());
    }

    /**
     * This test verifies that our hasItems
     * function works correctly for existing entries.
     */
    #[Group('lineitemgroup')]
    public function testHasItempsOnExistingList(): void
    {
        $group = new LineItemGroup();

        $group->addItem('ID1', 5);

        static::assertTrue($group->hasItems());
    }

    /**
     * This test verifies that our items
     * are correctly added if no entry exists
     * for the item id.
     */
    #[Group('lineitemgroup')]
    public function testAddInitialItem(): void
    {
        $group = new LineItemGroup();

        $group->addItem('ID1', 5);

        static::assertEquals('ID1', $group->getItems()[0]->getLineItemId());
        static::assertEquals(5, $group->getItems()[0]->getQuantity());
    }

    /**
     * This test verifies that our quantity
     * is correctly increased if we already have
     * an entry for the provided item id.
     */
    #[Group('lineitemgroup')]
    public function testAddQuantityToExisting(): void
    {
        $group = new LineItemGroup();

        $group->addItem('ID1', 5);
        $group->addItem('ID1', 2);

        static::assertEquals(7, $group->getItems()[0]->getQuantity());
    }
}
