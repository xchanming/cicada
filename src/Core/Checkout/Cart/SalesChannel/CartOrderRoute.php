<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartContextHasher;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\Order\OrderPersisterInterface;
use Cicada\Core\Checkout\Cart\TaxProvider\TaxProviderProcessor;
use Cicada\Core\Checkout\Gateway\SalesChannel\AbstractCheckoutGatewayRoute;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Payment\PaymentProcessor;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class CartOrderRoute extends AbstractCartOrderRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartCalculator $cartCalculator,
        private readonly EntityRepository $orderRepository,
        private readonly OrderPersisterInterface $orderPersister,
        private readonly AbstractCartPersister $cartPersister,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly TaxProviderProcessor $taxProviderProcessor,
        private readonly AbstractCheckoutGatewayRoute $checkoutGatewayRoute,
        private readonly CartContextHasher $cartContextHasher,
    ) {
    }

    public function getDecorated(): AbstractCartOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/checkout/order', name: 'store-api.checkout.cart.order', methods: ['POST'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true])]
    public function order(Cart $cart, SalesChannelContext $context, RequestDataBag $data): CartOrderRouteResponse
    {
        $hash = $data->getAlnum('hash');

        if ($hash && !$this->cartContextHasher->isMatching($hash, $cart, $context)) {
            throw CartException::hashMismatch($cart->getToken());
        }

        // we use this state in stock updater class, to prevent duplicate available stock updates
        $context->addState('checkout-order-route');

        $calculatedCart = $this->cartCalculator->calculate($cart, $context);

        $response = $this->checkoutGatewayRoute->load(new Request($data->all(), $data->all()), $cart, $context);
        $calculatedCart->addErrors(...$response->getErrors());

        $this->taxProviderProcessor->process($calculatedCart, $context);

        $this->addCustomerComment($calculatedCart, $data);
        $this->addAffiliateTracking($calculatedCart, $data);
        Profiler::trace('checkout-order::pre-payment', fn () => $this->paymentProcessor->validate($calculatedCart, $data, $context));
        $orderId = Profiler::trace('checkout-order::order-persist', fn () => $this->orderPersister->persist($calculatedCart, $context));

        $criteria = new Criteria([$orderId]);
        $criteria
            ->setTitle('order-route::order-loading')
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('orderCustomer.salutation')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('deliveries.shippingOrderAddress.countryState')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('lineItems.cover')
            ->addAssociation('lineItems.downloads.media')
            ->addAssociation('currency')
            ->addAssociation('addresses.country')
            ->addAssociation('addresses.countryState')
            ->addAssociation('stateMachineState')
            ->addAssociation('deliveries.stateMachineState')
            ->addAssociation('transactions.stateMachineState')
            ->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        $this->eventDispatcher->dispatch(new CheckoutOrderPlacedCriteriaEvent($criteria, $context));

        /** @var OrderEntity|null $orderEntity */
        $orderEntity = Profiler::trace('checkout-order::order-loading', fn () => $this->orderRepository->search($criteria, $context->getContext())->first());

        if (!$orderEntity) {
            throw CartException::invalidPaymentOrderNotStored($orderId);
        }

        $event = new CheckoutOrderPlacedEvent($context, $orderEntity);

        Profiler::trace('checkout-order::event-listeners', function () use ($event): void {
            $this->eventDispatcher->dispatch($event);
        });

        $this->cartPersister->delete($context->getToken(), $context);

        return new CartOrderRouteResponse($orderEntity);
    }

    private function addCustomerComment(Cart $cart, DataBag $data): void
    {
        $customerComment = ltrim(rtrim((string) $data->get(OrderService::CUSTOMER_COMMENT_KEY, '')));

        if ($customerComment === '') {
            return;
        }

        $cart->setCustomerComment($customerComment);
    }

    private function addAffiliateTracking(Cart $cart, DataBag $data): void
    {
        $affiliateCode = $data->get(OrderService::AFFILIATE_CODE_KEY);
        $campaignCode = $data->get(OrderService::CAMPAIGN_CODE_KEY);
        if ($affiliateCode) {
            $cart->setAffiliateCode($affiliateCode);
        }

        if ($campaignCode) {
            $cart->setCampaignCode($campaignCode);
        }
    }
}
