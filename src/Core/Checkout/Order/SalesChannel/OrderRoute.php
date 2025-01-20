<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Cart\Rule\PaymentMethodRule;
use Cicada\Core\Checkout\Order\Event\OrderCriteriaEvent;
use Cicada\Core\Checkout\Order\Exception\GuestNotAuthenticatedException;
use Cicada\Core\Checkout\Order\Exception\WrongGuestCredentialsException;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Promotion\PromotionCollection;
use Cicada\Core\Checkout\Promotion\PromotionEntity;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Adapter\Database\ReplicaConnection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\RateLimiter\Exception\RateLimitExceededException;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\Framework\Rule\Container\Container;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class OrderRoute extends AbstractOrderRoute
{
    /**
     * @internal
     *
     * @param EntityRepository<OrderCollection> $orderRepository
     * @param EntityRepository<PromotionCollection> $promotionRepository
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $promotionRepository,
        private readonly RateLimiter $rateLimiter,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/order', name: 'store-api.order', methods: ['GET', 'POST'], defaults: ['_entity' => 'order'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): OrderRouteResponse
    {
        ReplicaConnection::ensurePrimary();

        $criteria->addFilter(new EqualsFilter('order.salesChannelId', $context->getSalesChannelId()));

        $criteria->addAssociation('billingAddress');
        $criteria->addAssociation('orderCustomer.customer');

        $deepLinkFilter = \current(array_filter($criteria->getFilters(), static fn (Filter $filter) => \in_array('order.deepLinkCode', $filter->getFields(), true)
            || \in_array('deepLinkCode', $filter->getFields(), true))) ?: null;

        if ($context->getCustomer()) {
            $criteria->addFilter(new EqualsFilter('order.orderCustomer.customerId', $context->getCustomerId()));
        } elseif ($deepLinkFilter === null) {
            throw CartException::customerNotLoggedIn();
        }

        $this->eventDispatcher->dispatch(new OrderCriteriaEvent($criteria, $context));

        $orderResult = $this->orderRepository->search($criteria, $context->getContext());
        $orders = $orderResult->getEntities();

        // remove old orders only if there is a deeplink filter
        if ($deepLinkFilter !== null) {
            $orders = $this->filterOldOrders($orders);
        }

        // Handle guest authentication if deeplink is set
        if (!$context->getCustomer() && $deepLinkFilter instanceof EqualsFilter) {
            try {
                $cacheKey = strtolower((string) $deepLinkFilter->getValue()) . '-' . $request->getClientIp();

                $this->rateLimiter->ensureAccepted(RateLimiter::GUEST_LOGIN, $cacheKey);
            } catch (RateLimitExceededException $exception) {
                throw OrderException::customerAuthThrottledException($exception->getWaitTime(), $exception);
            }

            $order = $orders->first();
            $this->checkGuestAuth($order, $request);
        }

        if (isset($cacheKey)) {
            $this->rateLimiter->reset(RateLimiter::GUEST_LOGIN, $cacheKey);
        }

        $response = new OrderRouteResponse($orderResult);
        if ($request->get('checkPromotion') === true) {
            foreach ($orders as $order) {
                $promotions = $this->getActivePromotions($order, $context);
                $changeable = true;
                foreach ($promotions as $promotion) {
                    $changeable = $this->checkPromotion($promotion);
                    if ($changeable === true) {
                        break;
                    }
                }
                $response->addPaymentChangeable([$order->getId() => $changeable]);
            }
        }

        return $response;
    }

    private function getActivePromotions(OrderEntity $order, SalesChannelContext $context): PromotionCollection
    {
        $promotionIds = [];
        foreach ($order->getLineItems() ?? [] as $lineItem) {
            $payload = $lineItem->getPayload();
            if (isset($payload['promotionId']) && \is_string($payload['promotionId'])) {
                $promotionIds[] = $payload['promotionId'];
            }
        }

        $promotions = new PromotionCollection();

        if (!empty($promotionIds)) {
            $criteria = new Criteria($promotionIds);
            $criteria->addAssociation('cartRules');
            $promotions = $this->promotionRepository->search($criteria, $context->getContext())->getEntities();
        }

        return $promotions;
    }

    private function checkRuleType(Container $rule): bool
    {
        foreach ($rule->getRules() as $nestedRule) {
            if ($nestedRule instanceof Container && $this->checkRuleType($nestedRule) === false) {
                return false;
            }
            if ($nestedRule instanceof PaymentMethodRule) {
                return false;
            }
        }

        return true;
    }

    private function checkPromotion(PromotionEntity $promotion): bool
    {
        if ($promotion->getCartRules() === null) {
            return true;
        }

        foreach ($promotion->getCartRules() as $cartRule) {
            if (!$this->checkCartRule($cartRule)) {
                return false;
            }
        }

        return true;
    }

    private function checkCartRule(RuleEntity $cartRule): bool
    {
        $payload = $cartRule->getPayload();
        if (!$payload instanceof Container) {
            return true;
        }

        foreach ($payload->getRules() as $rule) {
            if ($rule instanceof Container && $this->checkRuleType($rule) === false) {
                return false;
            }
        }

        return true;
    }

    private function filterOldOrders(OrderCollection $orders): OrderCollection
    {
        // Search with deepLinkCode needs updatedAt Filter
        $latestOrderDate = (new \DateTime())->setTimezone(new \DateTimeZone('Asia/Shanghai'))->modify(-abs(30) . ' Day');

        return $orders->filter(fn (OrderEntity $order) => $order->getCreatedAt() > $latestOrderDate || $order->getUpdatedAt() > $latestOrderDate);
    }

    /**
     * @throws CustomerNotLoggedInException
     * @throws WrongGuestCredentialsException
     * @throws GuestNotAuthenticatedException
     */
    private function checkGuestAuth(?OrderEntity $order, Request $request): void
    {
        if ($order === null) {
            throw new GuestNotAuthenticatedException();
        }

        $orderCustomer = $order->getOrderCustomer();
        if ($orderCustomer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $guest = $orderCustomer->getCustomer() !== null && $orderCustomer->getCustomer()->getGuest();
        // Throw exception when customer is not guest
        if (!$guest) {
            throw CartException::customerNotLoggedIn();
        }

        // Verify email and zip code with this order
        if ($request->get('email', false) && $request->get('zipcode', false)) {
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress === null
                || $request->get('email') !== $orderCustomer->getEmail()
                || $request->get('zipcode') !== $billingAddress->getZipcode()) {
                throw new WrongGuestCredentialsException();
            }
        } else {
            throw new GuestNotAuthenticatedException();
        }
    }
}
