<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Controller;

use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Checkout\Payment\PaymentProcessor;
use Cicada\Core\Framework\CicadaException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
class PaymentController extends AbstractController
{
    /**
     * @internal
     *
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly PaymentProcessor $paymentProcessor,
        private readonly OrderConverter $orderConverter,
        private readonly TokenFactoryInterfaceV2 $tokenFactory,
        private readonly EntityRepository $orderRepository
    ) {
    }

    #[Route(path: '/payment/finalize-transaction', name: 'payment.finalize.transaction', methods: ['GET', 'POST'])]
    public function finalizeTransaction(Request $request): Response
    {
        $paymentToken = $request->get('_sw_payment_token');

        if ($paymentToken === null) {
            throw RoutingException::missingRequestParameter('_sw_payment_token');
        }

        $token = $this->tokenFactory->parseToken($paymentToken);
        if ($token->isExpired()) {
            $token->setException(PaymentException::tokenExpired($paymentToken));
            if ($token->getToken() !== null) {
                $this->tokenFactory->invalidateToken($token->getToken());
            }

            return $this->handleResponse($token);
        }

        $salesChannelContext = $this->assembleSalesChannelContext($token);

        $result = $this->paymentProcessor->finalize(
            $token,
            $request,
            $salesChannelContext
        );

        return $this->handleResponse($result);
    }

    private function handleResponse(TokenStruct $token): Response
    {
        if ($token->getException() === null) {
            $finishUrl = $token->getFinishUrl();
            if ($finishUrl) {
                return new RedirectResponse($finishUrl);
            }

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        if ($token->getErrorUrl() === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $url = $token->getErrorUrl();

        $exception = $token->getException();
        if ($exception instanceof CicadaException) {
            return new RedirectResponse(
                $url . (parse_url($url, \PHP_URL_QUERY) ? '&' : '?') . 'error-code=' . $exception->getErrorCode()
            );
        }

        return new RedirectResponse($url);
    }

    private function assembleSalesChannelContext(TokenStruct $token): SalesChannelContext
    {
        $context = Context::createDefaultContext();

        $transactionId = $token->getTransactionId();
        if ($transactionId === null) {
            throw PaymentException::invalidToken($token->getToken() ?? '');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('transactions.id', $transactionId));
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('orderCustomer');

        $order = $this->orderRepository->search($criteria, $context)->getEntities()->first();

        if ($order === null) {
            throw PaymentException::invalidToken($token->getToken() ?? '');
        }

        return $this->orderConverter->assembleSalesChannelContext($order, $context);
    }
}
