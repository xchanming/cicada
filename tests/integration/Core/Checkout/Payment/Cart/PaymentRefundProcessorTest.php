<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Payment\Cart;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStateHandler;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStates;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentRefundProcessor;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Integration\Builder\Order\OrderBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionCaptureBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionCaptureRefundBuilder;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentRefundProcessorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    private EntityRepository $orderRepository;

    private PaymentRefundProcessor $paymentRefundProcessor;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->paymentRefundProcessor = static::getContainer()->get(PaymentRefundProcessor::class);
    }

    public function testItThrowsIfRefundNotFound(): void
    {
        // capture has no refund
        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $this->ids->get('transaction')))
            ->build();

        $transaction = (new OrderTransactionBuilder($this->ids, 'transaction'))
            ->addCapture('capture', $capture)
            ->build();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->addTransaction('transaction', $transaction)
            ->build();

        $this->orderRepository->upsert([$order], Context::createDefaultContext());

        static::expectException(PaymentException::class);
        static::expectExceptionMessage('The Refund process failed with following exception: Unknown refund with id ' . $this->ids->get('refund') . '.');

        $this->paymentRefundProcessor->processRefund($this->ids->get('refund'), Context::createDefaultContext());
    }

    public function testItThrowsOnNotAvailableHandler(): void
    {
        $refund = (new OrderTransactionCaptureRefundBuilder(
            $this->ids,
            'refund',
            $this->ids->get('capture')
        ))
            ->add('stateId', $this->getStateMachineState(
                OrderTransactionCaptureRefundStates::STATE_MACHINE,
                OrderTransactionCaptureRefundStates::STATE_OPEN
            ))
            ->build();

        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $this->ids->get('transaction')))
            ->addRefund('refund', $refund)
            ->build();

        $transaction = (new OrderTransactionBuilder($this->ids, '10000'))
            ->addCapture('capture', $capture)
            ->add('paymentMethod', [
                'id' => $this->ids->get('payment_method'),
                // this enables refund handling for the payment method
                'technicalName' => 'payment_test',
                'handlerIdentifier' => AbstractPaymentHandler::class,
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => [
                        'name' => 'foo',
                    ],
                ],
            ])
            ->build();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->addTransaction('transaction', $transaction)
            ->build();

        $this->orderRepository->upsert([$order], Context::createDefaultContext());

        static::expectException(PaymentException::class);
        static::expectExceptionMessage('The Refund process failed with following exception: Unknown refund handler for refund id ' . $this->ids->get('refund') . '.');

        $this->paymentRefundProcessor->processRefund($this->ids->get('refund'), Context::createDefaultContext());
    }

    #[DataProvider('getInvalidStatesForTransitions')]
    public function testItThrowsIfRefundIsInWrongState(string $stateMachineState): void
    {
        $refund = (new OrderTransactionCaptureRefundBuilder(
            $this->ids,
            'refund',
            $this->ids->get('capture')
        ))
            ->add('stateId', $this->getStateMachineState(
                OrderTransactionCaptureRefundStates::STATE_MACHINE,
                $stateMachineState
            ))
            ->build();

        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $this->ids->get('transaction')))
            ->addRefund('refund', $refund)
            ->build();

        $transaction = (new OrderTransactionBuilder($this->ids, 'transaction'))
            ->addCapture('capture', $capture)
            ->build();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->addTransaction('transaction', $transaction)
            ->build();

        $this->orderRepository->upsert([$order], Context::createDefaultContext());

        static::expectException(PaymentException::class);
        static::expectExceptionMessage('The Refund process failed with following exception: Can not process refund with id ' . $refund['id'] . ' as refund has state ' . $stateMachineState . '.');

        $this->paymentRefundProcessor->processRefund($this->ids->get('refund'), Context::createDefaultContext());
    }

    public function testItCallsRefundHandler(): void
    {
        $handlerMock = $this->createMock(AbstractPaymentHandler::class);
        $handlerMock
            ->expects(static::once())
            ->method('refund');

        $handlerMock
            ->expects(static::once())
            ->method('supports')
            ->with(PaymentHandlerType::REFUND, $this->ids->get('payment_method'), Context::createDefaultContext())
            ->willReturn(true);

        $handlerRegistryMock = $this->createMock(PaymentHandlerRegistry::class);
        $handlerRegistryMock
            ->method('getPaymentMethodHandler')
            ->willReturn($handlerMock);

        $processor = new PaymentRefundProcessor(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(OrderTransactionCaptureRefundStateHandler::class),
            $handlerRegistryMock,
            static::getContainer()->get(PaymentTransactionStructFactory::class),
        );

        $refund = (new OrderTransactionCaptureRefundBuilder(
            $this->ids,
            'refund',
            $this->ids->get('capture')
        ))
            ->add('stateId', $this->getStateMachineState(
                OrderTransactionCaptureRefundStates::STATE_MACHINE,
                OrderTransactionCaptureRefundStates::STATE_OPEN
            ))
            ->build();

        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $this->ids->get('transaction')))
            ->addRefund('refund', $refund)
            ->build();

        $transaction = (new OrderTransactionBuilder($this->ids, '10000'))
            ->addCapture('capture', $capture)
            ->add('paymentMethod', [
                'id' => $this->ids->get('payment_method'),
                // this enables refund handling for the payment method
                'technicalName' => 'payment_test',
                'handlerIdentifier' => AbstractPaymentHandler::class,
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => [
                        'name' => 'foo',
                    ],
                ],
            ])
            ->build();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->addTransaction('transaction', $transaction)
            ->build();

        $this->orderRepository->upsert([$order], Context::createDefaultContext());

        $processor->processRefund(
            $this->ids->get('refund'),
            Context::createDefaultContext()
        );
    }

    public function testItCallsOldRefundHandler(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $handlerMock = $this->createMock(RefundPaymentHandlerInterface::class);
        $handlerMock
            ->expects(static::once())
            ->method('refund');

        $handlerRegistryMock = $this->createMock(PaymentHandlerRegistry::class);
        $handlerRegistryMock
            ->method('getPaymentMethodHandler')
            ->willReturn($handlerMock);

        $processor = new PaymentRefundProcessor(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(OrderTransactionCaptureRefundStateHandler::class),
            $handlerRegistryMock,
            static::getContainer()->get(PaymentTransactionStructFactory::class),
        );

        $refund = (new OrderTransactionCaptureRefundBuilder(
            $this->ids,
            'refund',
            $this->ids->get('capture')
        ))
            ->add('stateId', $this->getStateMachineState(
                OrderTransactionCaptureRefundStates::STATE_MACHINE,
                OrderTransactionCaptureRefundStates::STATE_OPEN
            ))
            ->build();

        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $this->ids->get('transaction')))
            ->addRefund('refund', $refund)
            ->build();

        $transaction = (new OrderTransactionBuilder($this->ids, '10000'))
            ->addCapture('capture', $capture)
            ->add('paymentMethod', [
                'id' => $this->ids->get('payment_method'),
                // this enables refund handling for the payment method
                'technicalName' => 'payment_test',
                'handlerIdentifier' => AbstractPaymentHandler::class,
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => [
                        'name' => 'foo',
                    ],
                ],
            ])
            ->build();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->addTransaction('transaction', $transaction)
            ->build();

        $this->orderRepository->upsert([$order], Context::createDefaultContext());

        $processor->processRefund(
            $this->ids->get('refund'),
            Context::createDefaultContext()
        );
    }

    /**
     * @return iterable<array<int, string>>
     */
    public static function getInvalidStatesForTransitions(): iterable
    {
        yield [OrderTransactionCaptureRefundStates::STATE_CANCELLED];
        yield [OrderTransactionCaptureRefundStates::STATE_COMPLETED];
        yield [OrderTransactionCaptureRefundStates::STATE_FAILED];
        yield [OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS];
    }
}
