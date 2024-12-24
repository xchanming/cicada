<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Order;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Checkout\Order\Exception\GuestNotAuthenticatedException;
use Cicada\Core\Checkout\Order\Exception\WrongGuestCredentialsException;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Cicada\Storefront\Framework\Page\StorefrontSearchResult;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('checkout')]
class AccountOrderPageLoader
{
    private const DEFAULT_LIMIT = 10;

    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - translator will be mandatory from 6.7
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractOrderRoute $orderRoute,
        private readonly AccountService $accountService,
        private readonly ?AbstractTranslator $translator = null
    ) {
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext): AccountOrderPage
    {
        if (!$salesChannelContext->getCustomer() && $request->get('deepLinkCode', false) === false) {
            throw CartException::customerNotLoggedIn();
        }

        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = AccountOrderPage::createFrom($page);
        $this->setMetaInformation($page);

        $orders = $this->getOrders($request, $salesChannelContext);
        if (!Feature::isActive('v6.7.0.0')) {
            $orders = StorefrontSearchResult::createFrom($orders);
        }

        $page->setOrders($orders);

        $page->setDeepLinkCode($request->get('deepLinkCode'));

        $firstOrder = $page->getOrders()->getEntities()->first();
        $orderCustomerId = $firstOrder?->getOrderCustomer()?->getCustomer()?->getId();
        if ($request->get('deepLinkCode') && $orderCustomerId !== null) {
            $this->accountService->loginById($orderCustomerId, $salesChannelContext);
        }

        $this->eventDispatcher->dispatch(
            new AccountOrderPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(AccountOrderPage $page): void
    {
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        if ($this->translator !== null && $page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        if ($this->translator !== null) {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('account.ordersMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        }
    }

    /**
     * @throws CustomerNotLoggedInException
     * @throws GuestNotAuthenticatedException
     * @throws WrongGuestCredentialsException
     *
     * @return EntitySearchResult<OrderCollection>
     */
    private function getOrders(Request $request, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = $this->createCriteria($request);
        $apiRequest = $request->duplicate();

        // Add email and zipcode for guest customer verification in order view
        if ($request->get('email', false) && $request->get('zipcode', false)) {
            $apiRequest->query->set('email', $request->get('email'));
            $apiRequest->query->set('zipcode', $request->get('zipcode'));
        }

        $event = new OrderRouteRequestEvent($request, $apiRequest, $context, $criteria);
        $this->eventDispatcher->dispatch($event);

        $responseStruct = $this->orderRoute
            ->load($event->getStoreApiRequest(), $context, $criteria);

        return $responseStruct->getOrders();
    }

    private function createCriteria(Request $request): Criteria
    {
        $page = $request->get('p');
        $page = $page ? (int) $page : 1;

        $criteria = (new Criteria())
            ->addSorting(new FieldSorting('order.createdAt', FieldSorting::DESCENDING))
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('transactions.stateMachineState')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('deliveries.stateMachineState')
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('lineItems')
            ->addAssociation('lineItems.cover')
            ->addAssociation('lineItems.downloads.media')
            ->addAssociation('addresses')
            ->addAssociation('currency')
            ->addAssociation('stateMachineState')
            ->addAssociation('documents.documentType')
            ->setLimit(self::DEFAULT_LIMIT)
            ->setOffset(($page - 1) * self::DEFAULT_LIMIT)
            ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        $criteria
            ->getAssociation('transactions')
            ->addSorting(new FieldSorting('createdAt'));

        $criteria
            ->addSorting(new FieldSorting('orderDateTime', FieldSorting::DESCENDING));

        if ($request->get('deepLinkCode')) {
            $criteria->addFilter(new EqualsFilter('deepLinkCode', $request->get('deepLinkCode')));
        }

        return $criteria;
    }
}
