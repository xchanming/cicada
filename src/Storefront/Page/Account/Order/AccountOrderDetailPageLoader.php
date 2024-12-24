<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Order;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('checkout')]
class AccountOrderDetailPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractOrderRoute $orderRoute
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): AccountOrderDetailPage
    {
        if (!$salesChannelContext->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }

        $orderId = (string) $request->get('id');

        if ($orderId === '') {
            throw RoutingException::missingRequestParameter('id');
        }

        $criteria = new Criteria([$orderId]);
        $criteria
            ->addAssociation('lineItems')
            ->addAssociation('orderCustomer')
            ->addAssociation('stateMachineState')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('transactions.stateMachineState')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('deliveries.stateMachineState')
            ->addAssociation('lineItems.cover');

        $criteria->getAssociation('transactions')
            ->addSorting(new FieldSorting('createdAt'));

        $apiRequest = $request->duplicate();

        $event = new OrderRouteRequestEvent($request, $apiRequest, $salesChannelContext, $criteria);
        $this->eventDispatcher->dispatch($event);

        $result = $this->orderRoute
            ->load($event->getStoreApiRequest(), $salesChannelContext, $criteria);

        $order = $result->getOrders()->first();

        if (!$order instanceof OrderEntity) {
            throw new NotFoundHttpException();
        }

        $page = AccountOrderDetailPage::createFrom($this->genericLoader->load($request, $salesChannelContext));
        $page->setLineItems($order->getNestedLineItems());
        $page->setOrder($order);

        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        $this->eventDispatcher->dispatch(
            new AccountOrderDetailPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
}
