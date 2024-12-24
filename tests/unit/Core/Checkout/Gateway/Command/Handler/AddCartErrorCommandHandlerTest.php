<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AddCartErrorCommand;
use Cicada\Core\Checkout\Gateway\Command\Handler\AddCartErrorCommandHandler;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;

/**
 * @internal
 */
#[CoversClass(AddCartErrorCommandHandler::class)]
#[Package('checkout')]
class AddCartErrorCommandHandlerTest extends TestCase
{
    public function testSupportedCommands(): void
    {
        static::assertSame(
            [AddCartErrorCommand::class],
            AddCartErrorCommandHandler::supportedCommands()
        );
    }

    public function testHandle(): void
    {
        $response = new CheckoutGatewayResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $firstCommand = new AddCartErrorCommand('Test Error', true, Error::LEVEL_ERROR);
        $secondCommand = new AddCartErrorCommand('A notice', false, Error::LEVEL_NOTICE);

        $context = Generator::createSalesChannelContext();

        $handler = new AddCartErrorCommandHandler();
        $handler->handle($firstCommand, $response, $context);
        $handler->handle($secondCommand, $response, $context);

        static::assertCount(2, $response->getCartErrors());

        $error1 = $response->getCartErrors()->first();

        static::assertNotNull($error1);
        static::assertSame('Test Error', $error1->getMessage());
        static::assertSame(Error::LEVEL_ERROR, $error1->getLevel());
        static::assertTrue($error1->blockOrder());

        $error2 = $response->getCartErrors()->last();

        static::assertNotNull($error2);
        static::assertSame('A notice', $error2->getMessage());
        static::assertSame(Error::LEVEL_NOTICE, $error2->getLevel());
        static::assertFalse($error2->blockOrder());
    }
}
