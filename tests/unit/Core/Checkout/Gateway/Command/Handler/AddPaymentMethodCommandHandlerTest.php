<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayException;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AddPaymentMethodCommand;
use Cicada\Core\Checkout\Gateway\Command\Handler\AddPaymentMethodCommandHandler;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;

/**
 * @internal
 */
#[CoversClass(AddPaymentMethodCommandHandler::class)]
#[Package('checkout')]
class AddPaymentMethodCommandHandlerTest extends TestCase
{
    public function testSupportedCommands(): void
    {
        static::assertSame(
            [AddPaymentMethodCommand::class],
            AddPaymentMethodCommandHandler::supportedCommands()
        );
    }

    public function testHandle(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setUniqueIdentifier(Uuid::randomHex());
        $paymentMethod->setTechnicalName('test');

        $result = new EntitySearchResult(
            PaymentMethodDefinition::ENTITY_NAME,
            1,
            new PaymentMethodCollection([$paymentMethod]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $repo = $this->createMock(EntityRepository::class);
        $repo
            ->expects(static::once())
            ->method('search')
            ->with(
                static::callback(
                    static function (Criteria $criteria): bool {
                        static::assertCount(1, $criteria->getFilters());

                        /** @var EqualsFilter $filter */
                        $filter = $criteria->getFilters()[0];

                        static::assertInstanceOf(EqualsFilter::class, $filter);
                        static::assertSame('technicalName', $filter->getField());
                        static::assertSame('test', $filter->getValue());

                        static::assertTrue($criteria->hasAssociation('appPaymentMethod'));
                        $assoc = $criteria->getAssociation('appPaymentMethod');
                        static::assertTrue($assoc->hasAssociation('app'));

                        return true;
                    }
                ),
                static::isInstanceOf(Context::class)
            )
            ->willReturn($result);

        $command = new AddPaymentMethodCommand('test');

        $response = new CheckoutGatewayResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $context = Generator::createSalesChannelContext();

        $handler = new AddPaymentMethodCommandHandler($repo, $this->createMock(ExceptionLogger::class));
        $handler->handle($command, $response, $context);

        static::assertSame($paymentMethod, $response->getAvailablePaymentMethods()->first());
    }

    public function testPaymentMethodNotFoundThrows(): void
    {
        $result = new EntitySearchResult(
            PaymentMethodDefinition::ENTITY_NAME,
            0,
            new PaymentMethodCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $repo = $this->createMock(EntityRepository::class);
        $repo
            ->expects(static::once())
            ->method('search')
            ->willReturn($result);

        $command = new AddPaymentMethodCommand('test');

        $response = new CheckoutGatewayResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $context = Generator::createSalesChannelContext();

        $logger = $this->createMock(ExceptionLogger::class);
        $logger
            ->expects(static::once())
            ->method('logOrThrowException')
            ->with(static::equalTo(CheckoutGatewayException::handlerException('Payment method "{{ technicalName }}" not found', ['technicalName' => 'test'])));

        $handler = new AddPaymentMethodCommandHandler($repo, $logger);
        $handler->handle($command, $response, $context);
    }
}
