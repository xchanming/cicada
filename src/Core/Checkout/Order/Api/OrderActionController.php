<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Api;

use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Payment\Cart\PaymentRefundProcessor;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction;
use Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class OrderActionController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentRefundProcessor $paymentRefundProcessor
    ) {
    }

    #[Route(path: '/api/_action/order/{orderId}/state/{transition}', name: 'api.action.order.state_machine.order.transition_state', methods: ['POST'])]
    public function orderStateTransition(
        string $orderId,
        string $transition,
        Request $request,
        Context $context
    ): JsonResponse {
        $mediaIds = $request->request->all('mediaIds');

        $context->addExtension(
            SendMailAction::MAIL_CONFIG_EXTENSION,
            new MailSendSubscriberConfig(
                $request->request->get('sendMail', true) === false,
                $mediaIds
            )
        );

        $toPlace = $this->orderService->orderStateTransition(
            $orderId,
            $transition,
            $request->request,
            $context
        );

        return new JsonResponse($toPlace->jsonSerialize());
    }

    #[Route(path: '/api/_action/order_transaction/{orderTransactionId}/state/{transition}', name: 'api.action.order.state_machine.order_transaction.transition_state', methods: ['POST'])]
    public function orderTransactionStateTransition(
        string $orderTransactionId,
        string $transition,
        Request $request,
        Context $context
    ): JsonResponse {
        $mediaIds = $request->request->all('mediaIds');

        $context->addExtension(
            SendMailAction::MAIL_CONFIG_EXTENSION,
            new MailSendSubscriberConfig(
                $request->request->get('sendMail', true) === false,
                $mediaIds
            )
        );

        $toPlace = $this->orderService->orderTransactionStateTransition(
            $orderTransactionId,
            $transition,
            $request->request,
            $context
        );

        return new JsonResponse($toPlace->jsonSerialize());
    }

    #[Route(path: '/api/_action/order_delivery/{orderDeliveryId}/state/{transition}', name: 'api.action.order.state_machine.order_delivery.transition_state', methods: ['POST'])]
    public function orderDeliveryStateTransition(
        string $orderDeliveryId,
        string $transition,
        Request $request,
        Context $context
    ): JsonResponse {
        $mediaIds = $request->request->all('mediaIds');

        $context->addExtension(
            SendMailAction::MAIL_CONFIG_EXTENSION,
            new MailSendSubscriberConfig(
                $request->request->get('sendMail', true) === false,
                $mediaIds
            )
        );

        $toPlace = $this->orderService->orderDeliveryStateTransition(
            $orderDeliveryId,
            $transition,
            $request->request,
            $context
        );

        return new JsonResponse($toPlace->jsonSerialize());
    }

    /**
     * @throws PaymentException
     */
    #[Route(path: '/api/_action/order_transaction_capture_refund/{refundId}', name: 'api.action.order.order_transaction_capture_refund', methods: ['POST'], defaults: ['_acl' => ['order_refund.editor']])]
    public function refundOrderTransactionCapture(string $refundId, Context $context): JsonResponse
    {
        $this->paymentRefundProcessor->processRefund($refundId, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
