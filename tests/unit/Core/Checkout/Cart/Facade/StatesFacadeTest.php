<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Facade;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Facade\StatesFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StatesFacade::class)]
class StatesFacadeTest extends TestCase
{
    public function testPublicApi(): void
    {
        $cart = new Cart('test');

        $facade = new StatesFacade($cart);
        static::assertFalse($facade->has('foo'));

        $facade->add('foo');
        static::assertTrue($facade->has('foo'));
        static::assertEquals(['foo'], $facade->get());

        $facade->remove('foo');
        static::assertFalse($facade->has('foo'));
    }
}
