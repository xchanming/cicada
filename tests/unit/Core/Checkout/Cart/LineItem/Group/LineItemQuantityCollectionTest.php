<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LineItemQuantityCollection::class)]
class LineItemQuantityCollectionTest extends TestCase
{
    /**
     * This test verifies that we can correctly
     * test if our collection has an entry
     * for the provided item id.
     */
    #[Group('lineitemgroup')]
    public function testHasItem(): void
    {
        $item1 = new LineItemQuantity('A', 2);

        $collection = new LineItemQuantityCollection([$item1]);

        static::assertTrue($collection->has('A'));
        static::assertFalse($collection->has('X'));
    }

    /**
     * This test verifies that we can successfully
     * compress our list of entries and combine them
     * into single entries with aggregated quantities.
     */
    #[Group('lineitemgroup')]
    public function testCompress(): void
    {
        $item1 = new LineItemQuantity('A', 2);
        $item2 = new LineItemQuantity('B', 3);
        $item3 = new LineItemQuantity('C', 1);
        $item4 = new LineItemQuantity('A', 5);
        $item5 = new LineItemQuantity('B', 2);

        $collection = new LineItemQuantityCollection([$item1, $item2, $item3, $item4, $item5]);

        $collection->compress();

        static::assertCount(3, $collection);

        static::assertEquals(7, $collection->getElements()[0]->getQuantity());
        static::assertEquals(5, $collection->getElements()[1]->getQuantity());
        static::assertEquals(1, $collection->getElements()[2]->getQuantity());
    }
}
