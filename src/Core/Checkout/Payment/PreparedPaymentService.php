<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Psr\Log\LoggerInterface;

/**
 * @deprecated tag:v6.7.0 - will be removed, use `PaymentProcessor` instead
 */
#[Package('checkout')]
class PreparedPaymentService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentHandlerRegistry $paymentHandlerRegistry,
        private readonly EntityRepository $appPaymentMethodRepository,
        private readonly LoggerInterface $logger,
        private readonly InitialStateIdLoader $initialStateIdLoader,
        private readonly AbstractPaymentTransactionStructFactory $paymentTransactionStructFactory,
    ) {
    }

    public function handlePreOrderPayment(
        Cart $cart,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): ?Struct {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead',
        );

        try {
            $paymentHandler = $this->getPaymentHandlerFromSalesChannelContext($salesChannelContext);
            if (!$paymentHandler) {
                throw PaymentException::unknownPaymentMethodById($salesChannelContext->getPaymentMethod()->getId());
            }

            if (!($paymentHandler instanceof PreparedPaymentHandlerInterface)) {
                return null;
            }

            return $paymentHandler->validate($cart, $dataBag, $salesChannelContext);
        } catch (PaymentException $e) {
            $customer = $salesChannelContext->getCustomer();
            $customerId = $customer !== null ? $customer->getId() : '';
            $this->logger->error('An error occurred during processing the validation of the payment. The order has not been placed yet.', ['customerId' => $customerId, 'exceptionMessage' => $e->getMessage(), 'exception' => $e]);

            throw $e;
        }
    }

    public function handlePostOrderPayment(
        OrderEntity $order,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        ?Struct $preOrderStruct
    ): void {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead',
        );

        try {
            $transaction = $this->getTransaction($order, $salesChannelContext);
            if ($transaction === null) {
                return;
            }

            $paymentHandler = $this->getPaymentHandlerFromTransaction($transaction);

            if (!($paymentHandler instanceof PreparedPaymentHandlerInterface)
                || $preOrderStruct === null) {
                return;
            }

            $preparedTransactionStruct = $this->paymentTransactionStructFactory->prepared($transaction, $order);
            $paymentHandler->capture($preparedTransactionStruct, $dataBag, $salesChannelContext, $preOrderStruct);
        } catch (PaymentException $e) {
            $this->logger->error('An error occurred during processing the capture of the payment. The order has been placed.', ['orderId' => $order->getId(), 'exceptionMessage' => $e->getMessage(), 'exception' => $e]);

            throw $e;
        }
    }

    private function getTransaction(OrderEntity $order, SalesChannelContext $salesChannelContext): ?OrderTransactionEntity
    {
        $transactions = $order->getTransactions();
        if ($transactions === null) {
            throw PaymentException::invalidOrder($order->getId());
        }

        $transactions = $transactions->filterByStateId(
            $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE)
        );

        return $transactions->last();
    }

    private function getPaymentHandlerFromTransaction(OrderTransactionEntity $transaction): AbstractPaymentHandler|PaymentHandlerInterface
    {
        $paymentMethod = $transaction->getPaymentMethod();
        if ($paymentMethod === null) {
            throw PaymentException::unknownPaymentMethodById($transaction->getPaymentMethodId());
        }

        $paymentHandler = $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethod->getId());
        if (!$paymentHandler) {
            throw PaymentException::unknownPaymentMethodById($paymentMethod->getId());
        }

        return $paymentHandler;
    }

    private function getPaymentHandlerFromSalesChannelContext(SalesChannelContext $salesChannelContext): AbstractPaymentHandler|PaymentHandlerInterface|null
    {
        $paymentMethod = $salesChannelContext->getPaymentMethod();

        if (($appPaymentMethod = $paymentMethod->getAppPaymentMethod()) && $appPaymentMethod->getApp()) {
            return $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethod->getId());
        }

        $criteria = new Criteria();
        $criteria->setTitle('prepared-payment-handler');
        $criteria->addAssociation('app');
        $criteria->addFilter(new EqualsFilter('paymentMethodId', $paymentMethod->getId()));

        /** @var AppPaymentMethodEntity $appPaymentMethod */
        $appPaymentMethod = $this->appPaymentMethodRepository->search($criteria, $salesChannelContext->getContext())->first();
        $paymentMethod->setAppPaymentMethod($appPaymentMethod);

        return $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethod->getId());
    }
}
