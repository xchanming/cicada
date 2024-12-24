<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Checkout\Gateway;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Gateway\Command\CheckoutGatewayCommandCollection;
use Cicada\Core\Checkout\Gateway\Command\Event\CheckoutGatewayCommandsCollectedEvent;
use Cicada\Core\Checkout\Gateway\Command\Executor\CheckoutGatewayCommandExecutor;
use Cicada\Core\Checkout\Gateway\Command\Registry\CheckoutGatewayCommandRegistry;
use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Checkout\Gateway\AppCheckoutGateway;
use Cicada\Core\Framework\App\Checkout\Gateway\AppCheckoutGatewayResponse;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayload;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayloadService;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use Cicada\Tests\Unit\Core\Checkout\Gateway\Command\_fixture\TestCheckoutGatewayCommand;
use Cicada\Tests\Unit\Core\Checkout\Gateway\Command\_fixture\TestCheckoutGatewayHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(AppCheckoutGateway::class)]
#[Package('checkout')]
class AppCheckoutGatewayTest extends TestCase
{
    public function testProcessWithoutAppsDoesNothing(): void
    {
        $appRepository = $this->createMock(EntityRepository::class);
        $appRepository
            ->expects(static::never())
            ->method('search');

        $gateway = new AppCheckoutGateway(
            $this->createMock(AppCheckoutGatewayPayloadService::class),
            new CheckoutGatewayCommandExecutor($this->getRegistry(), new ExceptionLogger('test', false, new NullLogger())),
            $this->createMock(CheckoutGatewayCommandRegistry::class),
            $appRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ExceptionLogger::class),
            $this->createMock(ActiveAppsLoader::class)
        );

        $gateway->process(new CheckoutGatewayPayloadStruct(new Cart('hatoken'), Generator::createSalesChannelContext(), new PaymentMethodCollection(), new ShippingMethodCollection()));
    }

    public function testProcess(): void
    {
        $context = Generator::createSalesChannelContext();

        $criteria = new Criteria();
        $criteria->addAssociation('paymentMethods');

        $criteria->addFilter(
            new EqualsFilter('active', true),
            new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('checkoutGatewayUrl', null),
            ]),
        );

        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setUniqueIdentifier(Uuid::randomHex());
        $app->setCheckoutGatewayUrl('https://example.com');

        $result = new EntitySearchResult(
            'app',
            1,
            new AppCollection([$app]),
            null,
            $criteria,
            $context->getContext()
        );

        $appRepo = $this->createMock(EntityRepository::class);
        $appRepo
            ->expects(static::once())
            ->method('search')
            ->with(static::equalTo($criteria))
            ->willReturn($result);

        $id = Uuid::randomHex();

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setUniqueIdentifier($id);
        $paymentMethod->setTechnicalName('payment-test');

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setUniqueIdentifier($id);
        $shippingMethod->setTechnicalName('shipping-test');

        $cart = new Cart('hatoken');
        $payments = new PaymentMethodCollection([$paymentMethod]);
        $shipments = new ShippingMethodCollection([$shippingMethod]);

        $payloadService = $this->createMock(AppCheckoutGatewayPayloadService::class);
        $payloadService
            ->expects(static::once())
            ->method('request')
            ->with(
                'https://example.com',
                static::equalTo(new AppCheckoutGatewayPayload($context, $cart, [$id => 'payment-test'], [$id => 'shipping-test'])),
                $app
            )
            ->willReturn(new AppCheckoutGatewayResponse([['command' => 'test', 'payload' => [['test-method']]]]));

        $registry = new CheckoutGatewayCommandRegistry([new TestCheckoutGatewayHandler()]);

        $expectedCollection = new CheckoutGatewayCommandCollection([new TestCheckoutGatewayCommand(['test-method'])]);

        $executor = new CheckoutGatewayCommandExecutor($this->getRegistry(), new ExceptionLogger('test', false, new NullLogger()));

        $payload = new CheckoutGatewayPayloadStruct($cart, $context, $payments, $shipments);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::equalTo(new CheckoutGatewayCommandsCollectedEvent($payload, $expectedCollection)));

        $loader = $this->createMock(ActiveAppsLoader::class);
        $loader->method('getActiveApps')->willReturn([$app]);

        $gateway = new AppCheckoutGateway(
            $payloadService,
            $executor,
            $registry,
            $appRepo,
            $eventDispatcher,
            $this->createMock(ExceptionLogger::class),
            $loader
        );

        $gateway->process($payload);
    }

    private function getRegistry(): CheckoutGatewayCommandRegistry
    {
        return new CheckoutGatewayCommandRegistry([new TestCheckoutGatewayHandler()]);
    }
}
