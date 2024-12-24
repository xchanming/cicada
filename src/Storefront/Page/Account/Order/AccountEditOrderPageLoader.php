<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Order;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Gateway\SalesChannel\AbstractCheckoutGatewayRoute;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Cicada\Storefront\Event\RouteRequest\PaymentMethodRouteRequestEvent;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('checkout')]
class AccountEditOrderPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractOrderRoute $orderRoute,
        private readonly AbstractCheckoutGatewayRoute $checkoutGatewayRoute,
        private readonly OrderConverter $orderConverter,
        private readonly OrderService $orderService,
        private readonly AbstractTranslator $translator,
        private readonly CartService $cartService
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     * @throws OrderException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): AccountEditOrderPage
    {
        if (!$salesChannelContext->getCustomer() && $request->get('deepLinkCode', false) === false) {
            throw CartException::customerNotLoggedIn();
        }

        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = AccountEditOrderPage::createFrom($page);
        $this->setMetaInformation($page);

        $orderRouteResponse = $this->getOrder($request, $salesChannelContext);

        /** @var OrderEntity $order */
        $order = $orderRouteResponse->getOrders()->first();

        if ($this->isOrderCancelled($order)) {
            throw OrderException::orderCancelled($order->getId());
        }

        if ($this->isOrderPaid($order)) {
            throw OrderException::orderAlreadyPaid($order->getId());
        }

        $page->setOrder($order);
        $page->setPaymentChangeable($this->isPaymentChangeable($orderRouteResponse, $page));
        $page->setPaymentMethods($this->getPaymentMethods($salesChannelContext, $request, $order));
        $page->setDeepLinkCode($request->get('deepLinkCode'));

        $this->eventDispatcher->dispatch(
            new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(AccountEditOrderPage $page): void
    {
        if ($page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        $page->getMetaInformation()?->setRobots('noindex,follow');
        $page->getMetaInformation()?->setMetaTitle(
            $this->translator->trans('account.completePaymentMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
        );
    }

    private function getOrder(Request $request, SalesChannelContext $context): OrderRouteResponse
    {
        $criteria = $this->createCriteria($request, $context);
        $apiRequest = $request->duplicate();
        $apiRequest->query->set('checkPromotion', 'true');

        $event = new OrderRouteRequestEvent($request, $apiRequest, $context, $criteria);
        $this->eventDispatcher->dispatch($event);

        return $this->orderRoute
            ->load($event->getStoreApiRequest(), $context, $criteria);
    }

    private function createCriteria(Request $request, SalesChannelContext $context): Criteria
    {
        if ($request->get('orderId')) {
            $criteria = new Criteria([$request->get('orderId')]);
        } else {
            $criteria = new Criteria();
        }
        $criteria->addAssociation('lineItems.cover')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('billingAddress.salutation')
            ->addAssociation('billingAddress.country')
            ->addAssociation('billingAddress.countryState')
            ->addAssociation('deliveries.shippingOrderAddress.salutation')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('deliveries.shippingOrderAddress.countryState')
            ->addAssociation('deliveries.stateMachineState')
            ->addAssociation('transactions.stateMachineState')
            ->addAssociation('stateMachineState');

        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        if ($context->getCustomer() && $context->getCustomer()->getId()) {
            $criteria->addFilter(new EqualsFilter('order.orderCustomer.customerId', $context->getCustomer()->getId()));
        } elseif ($request->get('deepLinkCode')) {
            $criteria->addFilter(new EqualsFilter('deepLinkCode', $request->get('deepLinkCode')));
        } else {
            throw CartException::customerNotLoggedIn();
        }

        return $criteria;
    }

    private function getPaymentMethods(SalesChannelContext $context, Request $request, OrderEntity $order): PaymentMethodCollection
    {
        $routeRequest = $request->duplicate();
        if (!Feature::isActive('v6.7.0.0')) {
            /**
             * @deprecated tag:v6.7.0 - onlyAvailable is no longer set in query
             */
            $routeRequest->query->set('onlyAvailable', '1');
        }

        $event = new PaymentMethodRouteRequestEvent($request, $routeRequest, $context);
        $this->eventDispatcher->dispatch($event);

        $cart = $this->orderConverter->convertToCart($order, $context->getContext());
        $orderContext = $this->orderConverter->assembleSalesChannelContext($order, $context->getContext());

        $cart->setToken($orderContext->getToken());
        $this->cartService->setCart($cart);

        $options = $this->checkoutGatewayRoute->load($event->getStoreApiRequest(), $cart, $orderContext);

        $paymentMethods = $options->getPaymentMethods()->filterByProperty('afterOrderEnabled', true);
        $paymentMethods->sortPaymentMethodsByPreference($context);

        return $paymentMethods;
    }

    private function isOrderCancelled(OrderEntity $order): bool
    {
        $stateMachineState = $order->getStateMachineState();

        if ($stateMachineState === null) {
            return false;
        }

        return $stateMachineState->getTechnicalName() === OrderStates::STATE_CANCELLED;
    }

    private function isOrderPaid(OrderEntity $order): bool
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return false;
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return false;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return false;
        }

        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_PAID;
    }

    private function isPaymentChangeable(OrderRouteResponse $orderRouteResponse, AccountEditOrderPage $page): bool
    {
        $isChangeableByResponse = $orderRouteResponse->getPaymentsChangeable()[$page->getOrder()->getId()] ?? true;
        $isChangeableByTransactionState = $this->orderService->isPaymentChangeableByTransactionState($page->getOrder());

        return $isChangeableByResponse && $isChangeableByTransactionState;
    }
}
