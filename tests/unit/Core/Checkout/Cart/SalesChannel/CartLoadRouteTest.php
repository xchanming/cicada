<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\CartFactory;
use Cicada\Core\Checkout\Cart\SalesChannel\CartLoadRoute;
use Cicada\Core\Checkout\Cart\TaxProvider\TaxProviderProcessor;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CartLoadRoute::class)]
class CartLoadRouteTest extends TestCase
{
    public function testLoadCartCreatesNewCart(): void
    {
        $newCart = new Cart('test');
        $factory = $this->createMock(CartFactory::class);
        $factory
            ->expects(static::once())
            ->method('createNew')
            ->with('test')
            ->willReturn($newCart);

        $persister = $this->createMock(AbstractCartPersister::class);
        $persister
            ->expects(static::once())
            ->method('load')
            ->with('test')
            ->willThrowException(CartException::tokenNotFound('test'));

        $calculatedCart = new Cart('calculated');
        $calculator = $this->createMock(CartCalculator::class);
        $calculator
            ->expects(static::once())
            ->method('calculate')
            ->with($newCart, $this->createMock(SalesChannelContext::class))
            ->willReturn($calculatedCart);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->expects(static::once())
            ->method('getToken')
            ->willReturn('test');

        $cartLoadRoute = new CartLoadRoute(
            $persister,
            $factory,
            $calculator,
            $this->createMock(TaxProviderProcessor::class),
        );

        static::assertSame($calculatedCart, $cartLoadRoute->load(new Request(), $salesChannelContext)->getCart());
    }
}
