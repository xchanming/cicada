<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Facade;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\DiscountFacade;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;

/**
 * @internal
 */
#[CoversClass(DiscountFacade::class)]
class DiscountFacadeTest extends TestCase
{
    public function testPublicApiAvailable(): void
    {
        $item = new LineItem('foo', 'foo', 'foo');
        $item->setLabel('foo');
        $facade = new DiscountFacade($item);

        static::assertEquals('foo', $facade->getId());
        static::assertEquals('foo', $facade->getLabel());
    }
}
