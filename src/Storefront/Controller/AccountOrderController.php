<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Checkout\Customer\Exception\CustomerAuthThrottledException;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\Exception\GuestNotAuthenticatedException;
use Cicada\Core\Checkout\Order\Exception\WrongGuestCredentialsException;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractCancelOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractHandlePaymentMethodRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Event\RouteRequest\CancelOrderRouteRequestEvent;
use Cicada\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Cicada\Storefront\Event\RouteRequest\SetPaymentOrderRouteRequestEvent;
use Cicada\Storefront\Page\Account\Order\AccountEditOrderPageLoadedHook;
use Cicada\Storefront\Page\Account\Order\AccountEditOrderPageLoader;
use Cicada\Storefront\Page\Account\Order\AccountOrderDetailPageLoadedHook;
use Cicada\Storefront\Page\Account\Order\AccountOrderDetailPageLoader;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoadedHook;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('framework')]
class AccountOrderController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AccountOrderPageLoader $orderPageLoader,
        private readonly AccountEditOrderPageLoader $accountEditOrderPageLoader,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractCancelOrderRoute $cancelOrderRoute,
        private readonly AbstractSetPaymentOrderRoute $setPaymentOrderRoute,
        private readonly AbstractHandlePaymentMethodRoute $handlePaymentMethodRoute,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AccountOrderDetailPageLoader $orderDetailPageLoader,
        private readonly AbstractOrderRoute $orderRoute,
        private readonly SalesChannelContextServiceInterface $contextService,
        private readonly SystemConfigService $systemConfigService,
        private readonly OrderService $orderService
    ) {
    }

    #[Route(path: '/account/order', name: 'frontend.account.order.page', options: ['seo' => false], defaults: ['XmlHttpRequest' => true, '_loginRequired' => true, '_loginRequiredAllowGuest' => true, '_noStore' => true], methods: ['GET', 'POST'])]
    #[Route(path: '/account/order', name: 'frontend.account.order.page', options: ['seo' => false], defaults: ['XmlHttpRequest' => true, '_noStore' => true], methods: ['GET', 'POST'])]
    public function orderOverview(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->orderPageLoader->load($request, $context);

        $this->hook(new AccountOrderPageLoadedHook($page, $context));

        return $this->renderStorefront('@Storefront/storefront/page/account/order-history/index.html.twig', ['page' => $page]);
    }

    #[Route(path: '/account/order/cancel', name: 'frontend.account.order.cancel', methods: ['POST'])]
    public function cancelOrder(Request $request, SalesChannelContext $context): Response
    {
        $cancelOrderRequestData = [
            'orderId' => $request->get('orderId'),
            'transition' => 'cancel',
        ];

        $cancelOrderRequest = $request->duplicate(null, $cancelOrderRequestData);
        $event = new CancelOrderRouteRequestEvent($request, $cancelOrderRequest, $context);
        $this->eventDispatcher->dispatch($event);

        $this->cancelOrderRoute->cancel($event->getStoreApiRequest(), $context);

        if ($context->getCustomer() && $context->getCustomer()->getGuest() === true) {
            return $this->redirectToRoute(
                'frontend.account.order.single.page',
                [
                    'deepLinkCode' => $request->get('deepLinkCode'),
                ]
            );
        }

        return $this->redirectToRoute('frontend.account.order.page');
    }

    #[Route(path: '/account/order/{deepLinkCode}', name: 'frontend.account.order.single.page', options: ['seo' => false], defaults: ['_noStore' => true], methods: ['GET', 'POST'])]
    public function orderSingleOverview(Request $request, SalesChannelContext $context): Response
    {
        try {
            $page = $this->orderPageLoader->load($request, $context);

            $this->hook(new AccountOrderPageLoadedHook($page, $context));
        } catch (GuestNotAuthenticatedException|WrongGuestCredentialsException|CustomerAuthThrottledException $exception) {
            return $this->redirectToRoute(
                'frontend.account.guest.login.page',
                [
                    'redirectTo' => 'frontend.account.order.single.page',
                    'redirectParameters' => ['deepLinkCode' => $request->get('deepLinkCode')],
                    'loginError' => ($exception instanceof WrongGuestCredentialsException),
                    'waitTime' => ($exception instanceof CustomerAuthThrottledException) ? $exception->getWaitTime() : '',
                ]
            );
        }

        return $this->renderStorefront('@Storefront/storefront/page/account/order-history/index.html.twig', ['page' => $page]);
    }

    #[Route(path: '/widgets/account/order/detail/{id}', name: 'widgets.account.order.detail', options: ['seo' => false], defaults: ['XmlHttpRequest' => true, '_loginRequired' => true], methods: ['GET'])]
    public function ajaxOrderDetail(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->orderDetailPageLoader->load($request, $context);

        $this->hook(new AccountOrderDetailPageLoadedHook($page, $context));

        $response = $this->renderStorefront('@Storefront/storefront/page/account/order-history/order-detail-list.html.twig', [
            'orderDetails' => $page->getLineItems(),
            'orderId' => $page->getOrder()->getId(),
            'page' => $page,
        ]);

        $response->headers->set('x-robots-tag', 'noindex');

        return $response;
    }

    #[Route(path: '/account/order/edit/{orderId}', name: 'frontend.account.edit-order.page', defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true, '_noStore' => true], methods: ['GET'])]
    #[Route(path: '/account/order/edit/{orderId}', name: 'frontend.account.edit-order.page', defaults: ['_noStore' => true], methods: ['GET'])]
    public function editOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        $criteria = new Criteria([$orderId]);
        $deliveriesCriteria = $criteria->getAssociation('deliveries');
        $deliveriesCriteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

        try {
            /** @var OrderEntity|null $order */
            $order = $this->orderRoute->load($request, $context, $criteria)->getOrders()->first();
        } catch (InvalidUuidException) {
            $order = null;
        }

        if ($order === null) {
            $this->addFlash(self::DANGER, $this->trans('error.' . OrderException::ORDER_ORDER_NOT_FOUND_CODE));

            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($context->getCurrency()->getId() !== $order->getCurrencyId()) {
            $this->contextSwitchRoute->switchContext(
                new RequestDataBag([SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()]),
                $context
            );

            return $this->redirectToRoute('frontend.account.edit-order.page', ['orderId' => $orderId]);
        }

        /** @var OrderDeliveryEntity|null $mostCurrentDelivery */
        $mostCurrentDelivery = $order->getDeliveries()?->last();

        if ($mostCurrentDelivery !== null && $context->getShippingMethod()->getId() !== $mostCurrentDelivery->getShippingMethodId()) {
            $this->contextSwitchRoute->switchContext(
                new RequestDataBag([SalesChannelContextService::SHIPPING_METHOD_ID => $mostCurrentDelivery->getShippingMethodId()]),
                $context
            );

            return $this->redirectToRoute('frontend.account.edit-order.page', ['orderId' => $orderId]);
        }

        try {
            $page = $this->accountEditOrderPageLoader->load($request, $context);
        } catch (OrderException $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.' . $exception->getErrorCode(), ['%orderNumber%' => $order->getOrderNumber()]));

            return $this->redirectToRoute('frontend.account.order.page');
        }

        $this->hook(new AccountEditOrderPageLoadedHook($page, $context));

        if ($page->isPaymentChangeable() === false) {
            $refundsEnabled = $this->systemConfigService->get('core.cart.enableOrderRefunds');

            if ($refundsEnabled) {
                $this->addFlash(self::DANGER, $this->trans('account.editOrderPaymentNotChangeableWithRefunds'));
            } else {
                $this->addFlash(self::DANGER, $this->trans('account.editOrderPaymentNotChangeable'));
            }
        }

        $page->setErrorCode($request->get('error-code'));

        return $this->renderStorefront('@Storefront/storefront/page/account/order/index.html.twig', ['page' => $page]);
    }

    #[Route(path: '/account/order/payment/{orderId}', name: 'frontend.account.edit-order.change-payment-method', methods: ['POST'])]
    public function orderChangePayment(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        $this->contextSwitchRoute->switchContext(
            new RequestDataBag(
                [
                    SalesChannelContextService::PAYMENT_METHOD_ID => $request->get('paymentMethodId'),
                ]
            ),
            $context
        );

        return $this->redirectToRoute('frontend.account.edit-order.page', ['orderId' => $orderId]);
    }

    #[Route(path: '/account/order/update/{orderId}', name: 'frontend.account.edit-order.update-order', methods: ['POST'])]
    public function updateOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        $finishUrl = $this->generateUrl('frontend.checkout.finish.page', [
            'orderId' => $orderId,
            'changedPayment' => true,
        ]);

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.stateMachineState');
        /** @var OrderEntity|null $order */
        $order = $this->orderRoute->load($request, $context, $criteria)->getOrders()->first();

        if ($order === null) {
            throw OrderException::orderNotFound($orderId);
        }

        if (!$this->orderService->isPaymentChangeableByTransactionState($order)) {
            throw OrderException::paymentMethodNotChangeable();
        }

        if ($context->getCurrency()->getId() !== $order->getCurrencyId()) {
            $this->contextSwitchRoute->switchContext(
                new RequestDataBag([SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()]),
                $context
            );

            $context = $this->contextService->get(
                new SalesChannelContextServiceParameters(
                    $context->getSalesChannelId(),
                    $context->getToken(),
                    $context->getContext()->getLanguageId()
                )
            );
        }

        $errorUrl = $this->generateUrl('frontend.account.edit-order.page', ['orderId' => $orderId]);

        $setPaymentRequestData = array_merge($request->request->all(), ['orderId' => $orderId]);
        $setPaymentRequest = $request->duplicate(null, $setPaymentRequestData);

        $setPaymentOrderRouteRequestEvent = new SetPaymentOrderRouteRequestEvent($request, $setPaymentRequest, $context);
        $this->eventDispatcher->dispatch($setPaymentOrderRouteRequestEvent);

        $this->setPaymentOrderRoute->setPayment($setPaymentOrderRouteRequestEvent->getStoreApiRequest(), $context);

        $handlePaymentRequestData = array_merge($request->request->all(), [
            'orderId' => $orderId,
            'finishUrl' => $finishUrl,
            'errorUrl' => $errorUrl,
        ]);

        $handlePaymentRequest = $request->duplicate(null, $handlePaymentRequestData);

        $handlePaymentMethodRouteRequestEvent = new HandlePaymentMethodRouteRequestEvent($request, $handlePaymentRequest, $context);
        $this->eventDispatcher->dispatch($handlePaymentMethodRouteRequestEvent);

        try {
            $routeResponse = $this->handlePaymentMethodRoute->load(
                $handlePaymentMethodRouteRequestEvent->getStoreApiRequest(),
                $context
            );
            $response = $routeResponse->getRedirectResponse();
        } catch (PaymentException) {
            return $this->forwardToRoute(
                'frontend.checkout.finish.page',
                ['orderId' => $orderId, 'changedPayment' => true, 'paymentFailed' => true]
            );
        }

        return $response ?? $this->redirectToRoute(
            'frontend.checkout.finish.page',
            ['orderId' => $orderId, 'changedPayment' => true]
        );
    }
}
