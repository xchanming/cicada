<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LineItemQuantity::class)]
class LineItemQuantityTest extends TestCase
{
    /**
     * This test verifies that our property is correctly
     * assigned and returned in its getter.
     */
    #[Group('lineitemgroup')]
    public function testPropertyLineItemId(): void
    {
        $item = new LineItemQuantity('ID1', 2);

        static::assertEquals('ID1', $item->getLineItemId());
    }

    /**
     * This test verifies that our property is correctly
     * assigned and returned in its getter.
     */
    #[Group('lineitemgroup')]
    public function testPropertQuantity(): void
    {
        $item = new LineItemQuantity('ID1', 2);

        static::assertEquals(2, $item->getQuantity());
    }

    /**
     * This test verifies that its possible
     * to adjust the quantity value of this object.
     */
    #[Group('lineitemgroup')]
    public function testSetQuantity(): void
    {
        $item = new LineItemQuantity('ID1', 2);

        $item->setQuantity(5);

        static::assertEquals(5, $item->getQuantity());
    }
}
