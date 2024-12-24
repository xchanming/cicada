<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionChainProcessor;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Cicada\Core\Checkout\Payment\Event\FinalizePaymentOrderTransactionCriteriaEvent;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - will be removed, use `PaymentProcessor` instead
 */
#[Package('checkout')]
class PaymentService
{
    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     * @param EntityRepository<OrderCollection> $orderRepository
     *
     * @internal
     */
    public function __construct(
        private readonly PaymentTransactionChainProcessor $paymentProcessor,
        private readonly TokenFactoryInterfaceV2 $tokenFactory,
        private readonly PaymentHandlerRegistry $paymentHandlerRegistry,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $orderRepository,
        private readonly SalesChannelContextServiceInterface $contextService,
        private readonly AbstractPaymentTransactionStructFactory $paymentTransactionStructFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handlePaymentByOrder(
        string $orderId,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        ?string $finishUrl = null,
        ?string $errorUrl = null
    ): ?RedirectResponse {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead',
        );

        if (!Uuid::isValid($orderId)) {
            throw PaymentException::invalidOrder($orderId);
        }

        $criteria = new Criteria([$orderId]);
        $criteria->setTitle('payment-service::load-order');
        /** @var OrderEntity $order */
        $order = $this->orderRepository
            ->search($criteria, $context->getContext())
            ->first();

        if ($order === null) {
            throw PaymentException::invalidOrder($orderId);
        }

        if ($context->getCurrency()->getId() !== $order->getCurrencyId()) {
            $context = $this->contextService->get(
                new SalesChannelContextServiceParameters(
                    $context->getSalesChannelId(),
                    $context->getToken(),
                    $context->getContext()->getLanguageId(),
                    $order->getCurrencyId()
                )
            );
        }

        try {
            return $this->paymentProcessor->process($orderId, $dataBag, $context, $finishUrl, $errorUrl);
        } catch (PaymentException $e) {
            $transactionId = $e->getOrderTransactionId();
            $this->logger->error('An error occurred during processing the payment', ['orderTransactionId' => $transactionId, 'exceptionMessage' => $e->getMessage(), 'exception' => $e]);
            if ($transactionId !== null) {
                $this->transactionStateHandler->fail($transactionId, $context->getContext());
            }
            if ($errorUrl !== null) {
                $errorUrl .= (parse_url($errorUrl, \PHP_URL_QUERY) ? '&' : '?') . 'error-code=' . $e->getErrorCode();

                return new RedirectResponse($errorUrl);
            }

            throw $e;
        }
    }

    public function finalizeTransaction(string $paymentToken, Request $request, SalesChannelContext $context): TokenStruct
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead',
        );

        $token = $this->tokenFactory->parseToken($paymentToken);

        if ($token->isExpired()) {
            $token->setException(PaymentException::tokenExpired($paymentToken));
            if ($token->getToken() !== null) {
                $this->tokenFactory->invalidateToken($token->getToken());
            }

            return $token;
        }

        if ($token->getPaymentMethodId() === null) {
            throw PaymentException::invalidToken($paymentToken);
        }

        $transactionId = $token->getTransactionId();

        if ($transactionId === null || !Uuid::isValid($transactionId)) {
            throw PaymentException::asyncProcessInterrupted((string) $transactionId, 'Payment JWT didn\'t contain a valid orderTransactionId');
        }

        $transaction = $this->getPaymentTransactionStruct($transactionId, $context);

        $paymentHandler = $this->getPaymentHandlerById($token->getPaymentMethodId());

        try {
            $paymentHandler->finalize($transaction, $request, $context);
        } catch (PaymentException $e) {
            if ($e->getErrorCode() === PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL) {
                $this->transactionStateHandler->cancel($transactionId, $context->getContext());
            } else {
                $this->logger->error('An error occurred during finalizing async payment', ['orderTransactionId' => $transactionId, 'exceptionMessage' => $e->getMessage(), 'exception' => $e]);
                $this->transactionStateHandler->fail($transactionId, $context->getContext());
            }
            $token->setException($e);
        } finally {
            if ($token->getToken() !== null) {
                $this->tokenFactory->invalidateToken($token->getToken());
            }
        }

        return $token;
    }

    private function getPaymentHandlerById(string $paymentMethodId): AsynchronousPaymentHandlerInterface
    {
        $handler = $this->paymentHandlerRegistry->getAsyncPaymentHandler($paymentMethodId);

        if (!$handler) {
            throw PaymentException::unknownPaymentMethodById($paymentMethodId);
        }

        return $handler;
    }

    private function getPaymentTransactionStruct(string $orderTransactionId, SalesChannelContext $context): AsyncPaymentTransactionStruct
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->setTitle('payment-service::load-transaction');
        $criteria->addAssociation('order');
        $criteria->addAssociation('paymentMethod.appPaymentMethod.app');

        $this->eventDispatcher->dispatch(new FinalizePaymentOrderTransactionCriteriaEvent($orderTransactionId, $criteria, $context));

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context->getContext())->first();

        if ($orderTransaction === null || $orderTransaction->getOrder() === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        return $this->paymentTransactionStructFactory->async($orderTransaction, $orderTransaction->getOrder(), '');
    }
}
