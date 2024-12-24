<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command\Handler;

use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\Handler\RemovePaymentMethodCommandHandler;
use Cicada\Core\Checkout\Gateway\Command\RemovePaymentMethodCommand;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemovePaymentMethodCommandHandler::class)]
#[Package('checkout')]
class RemovePaymentMethodCommandHandlerTest extends TestCase
{
    public function testSupportedCommands(): void
    {
        static::assertSame(
            [RemovePaymentMethodCommand::class],
            RemovePaymentMethodCommandHandler::supportedCommands()
        );
    }

    public function testHandle(): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod1->setUniqueIdentifier(Uuid::randomHex());
        $paymentMethod1->setTechnicalName('test-1');

        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod2->setUniqueIdentifier(Uuid::randomHex());
        $paymentMethod2->setTechnicalName('test-2');

        $paymentMethods = new PaymentMethodCollection([$paymentMethod1, $paymentMethod2]);

        $response = new CheckoutGatewayResponse(
            $paymentMethods,
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $command = new RemovePaymentMethodCommand('test-1');

        $handler = new RemovePaymentMethodCommandHandler();
        $handler->handle($command, $response, Generator::createSalesChannelContext());

        static::assertCount(1, $response->getAvailablePaymentMethods());
        static::assertNotNull($response->getAvailablePaymentMethods()->first());
        static::assertSame('test-2', $response->getAvailablePaymentMethods()->first()->getTechnicalName());
    }
}
