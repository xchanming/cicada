<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionChainProcessor;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Cicada\Core\Checkout\Payment\Event\PayPaymentOrderCriteriaEvent;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed
 */
#[Package('checkout')]
#[CoversClass(PaymentTransactionChainProcessor::class)]
class PaymentTransactionChainProcessorTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    #[DisabledFeatures(['v6.7.0.0'])]
    public function testThrowsExceptionOnNullOrder(): void
    {
        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'order',
                    0,
                    new EntityCollection([]),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(PayPaymentOrderCriteriaEvent::class));

        $processor = new PaymentTransactionChainProcessor(
            $this->createMock(TokenFactoryInterfaceV2::class),
            $orderRepository,
            $this->createMock(RouterInterface::class),
            $this->createMock(PaymentHandlerRegistry::class),
            $this->createMock(SystemConfigService::class),
            $this->createMock(InitialStateIdLoader::class),
            new PaymentTransactionStructFactory(),
            $eventDispatcher,
        );

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(\sprintf('The order with id %s is invalid or could not be found.', $this->ids->get('test-order')));

        $processor->process(
            $this->ids->get('test-order'),
            new RequestDataBag(),
            Generator::createSalesChannelContext()
        );
    }

    #[DisabledFeatures(['v6.7.0.0'])]
    public function testThrowsExceptionOnNullPaymentHandler(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setStateId($this->ids->get('order-state'));
        $transaction->setPaymentMethodId($this->ids->get('payment'));

        $order = new OrderEntity();
        $order->setUniqueIdentifier($this->ids->get('test-order'));
        $order->setTransactions(new OrderTransactionCollection([$transaction]));

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'order',
                    1,
                    new EntityCollection([$order]),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );

        $paymentHandlerRegistry = $this->createMock(PaymentHandlerRegistry::class);
        $paymentHandlerRegistry
            ->method('getPaymentMethodHandler')
            ->with($this->ids->get('payment'))
            ->willReturn(null);

        $initialStateIdLoader = $this->createMock(InitialStateIdLoader::class);
        $initialStateIdLoader
            ->method('get')
            ->willReturn($this->ids->get('order-state'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(PayPaymentOrderCriteriaEvent::class));

        $processor = new PaymentTransactionChainProcessor(
            $this->createMock(TokenFactoryInterfaceV2::class),
            $orderRepository,
            $this->createMock(RouterInterface::class),
            $paymentHandlerRegistry,
            $this->createMock(SystemConfigService::class),
            $initialStateIdLoader,
            new PaymentTransactionStructFactory(),
            $eventDispatcher,
        );

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(\sprintf('Could not find payment method with id "%s"', $this->ids->get('payment')));

        $processor->process(
            $this->ids->get('test-order'),
            new RequestDataBag(),
            Generator::createSalesChannelContext()
        );
    }

    #[DisabledFeatures(['v6.7.0.0'])]
    public function testProcessSync(): void
    {
        $transactionId = Uuid::randomHex();
        $transaction = new OrderTransactionEntity();
        $transaction->setId($transactionId);
        $transaction->setPaymentMethodId(Uuid::randomHex());
        $transaction->setStateId(OrderTransactionStates::STATE_OPEN);
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setTransactions(new OrderTransactionCollection([$transaction]));
        $context = Generator::createSalesChannelContext();
        $requestDataBag = new RequestDataBag();

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(static::once())->method('dispatch')->with(static::isInstanceOf(PayPaymentOrderCriteriaEvent::class));

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->expects(static::once())->method('search')->willReturn(new EntitySearchResult('order', 1, new OrderCollection([$order]), null, new Criteria(), $context->getContext()));

        $struct = new SyncPaymentTransactionStruct($transaction, $order);
        $paymentStructFactory = $this->createMock(AbstractPaymentTransactionStructFactory::class);
        $paymentStructFactory->expects(static::once())->method('sync')->willReturn($struct);

        $paymentHandler = $this->createMock(SynchronousPaymentHandlerInterface::class);
        $paymentHandler->expects(static::once())->method('pay')->with($struct, $requestDataBag, $context);

        $paymentHandlerRegistry = $this->createMock(PaymentHandlerRegistry::class);
        $paymentHandlerRegistry->expects(static::once())->method('getPaymentMethodHandler')->with($transaction->getPaymentMethodId())->willReturn($paymentHandler);

        $initialStateIdLoader = $this->createMock(InitialStateIdLoader::class);
        $initialStateIdLoader->expects(static::once())->method('get')->willReturn(OrderTransactionStates::STATE_OPEN);

        $processor = new PaymentTransactionChainProcessor(
            $this->createMock(TokenFactoryInterfaceV2::class),
            $orderRepository,
            $this->createMock(RouterInterface::class),
            $paymentHandlerRegistry,
            new StaticSystemConfigService([]),
            $initialStateIdLoader,
            $paymentStructFactory,
            $eventDispatcher,
        );

        static::assertNull($processor->process('orderId', $requestDataBag, $context));
    }
}
