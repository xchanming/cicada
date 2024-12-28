<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Order;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\Transformer\AddressTransformer;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Processor;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Checkout\Order\Exception\EmptyCartException;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Promotion\Cart\PromotionCollector;
use Cicada\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class RecalculationService
{
    /**
     * @internal
     *
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        protected EntityRepository $orderRepository,
        protected OrderConverter $orderConverter,
        protected CartService $cartService,
        protected EntityRepository $productRepository,
        protected EntityRepository $orderAddressRepository,
        protected EntityRepository $customerAddressRepository,
        protected EntityRepository $orderLineItemRepository,
        protected Processor $processor,
        private readonly CartRuleLoader $cartRuleLoader,
        private readonly PromotionItemBuilder $promotionItemBuilder
    ) {
    }

    /**
     * @param array<string, array<string, bool>|string> $salesChannelContextOptions
     *
     * @throws CustomerNotLoggedInException
     * @throws CartException
     * @throws EmptyCartException
     * @throws InconsistentCriteriaIdsException
     */
    public function recalculateOrder(string $orderId, Context $context, array $salesChannelContextOptions = []): void
    {
        $order = $this->fetchOrder($orderId, $context);

        $this->validateOrder($order, $orderId);
        \assert($order instanceof OrderEntity);

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($order, $context, $salesChannelContextOptions);
        $cart = $this->orderConverter->convertToCart($order, $context);
        $recalculatedCart = $this->recalculateCart($cart, $salesChannelContext);

        $shouldIncludeDeliveries = \count($cart->getLineItems()) > 0;
        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries($shouldIncludeDeliveries)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();
        $orderData['stateId'] = $order->getStateId();

        if ($order->getDeliveries()?->first()?->getStateId() && $shouldIncludeDeliveries) {
            $orderData['deliveries'][0]['stateId'] = $order->getDeliveries()->first()->getStateId();
        }

        // change scope to be able to write protected state fields of transactions and deliveries
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderData, $order): void {
            $orderDataLineItemIds = array_column($orderData['lineItems'], 'id');

            if (($lineItems = $order->getLineItems()) instanceof OrderLineItemCollection) {
                $this->orderLineItemRepository->delete(
                    array_values($lineItems->fmap(
                        static fn (OrderLineItemEntity $lineItem) => !\in_array($lineItem->getId(), $orderDataLineItemIds, true) ? ['id' => $lineItem->getId()] : null
                    )),
                    $context
                );
            }

            $this->orderRepository->upsert([$orderData], $context);
        });
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws CartException
     * @throws ProductNotFoundException
     */
    public function addProductToOrder(string $orderId, string $productId, int $quantity, Context $context): void
    {
        $this->validateProduct($productId, $context);
        $lineItem = (new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, $quantity))
            ->setRemovable(true)
            ->setStackable(true);

        $order = $this->fetchOrder($orderId, $context);

        $this->validateOrder($order, $orderId);

        \assert($order instanceof OrderEntity);

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($order, $context);
        $cart = $this->orderConverter->convertToCart($order, $context);
        $cart->add($lineItem);

        $recalculatedCart = $this->recalculateCart($cart, $salesChannelContext);

        $new = $cart->get($lineItem->getId());
        if ($new) {
            $this->addProductToDeliveryPosition($new, $recalculatedCart);
        }

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(true)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();
        $orderData['stateId'] = $order->getStateId();
        if ($order->getDeliveries()?->first()?->getStateId()) {
            $orderData['deliveries'][0]['stateId'] = $order->getDeliveries()->first()->getStateId();
        }

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderData): void {
            $this->orderRepository->upsert([$orderData], $context);
        });
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws CartException
     */
    public function addCustomLineItem(string $orderId, LineItem $lineItem, Context $context): void
    {
        $order = $this->fetchOrder($orderId, $context);

        $this->validateOrder($order, $orderId);
        \assert($order instanceof OrderEntity);

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($order, $context);
        $cart = $this->orderConverter->convertToCart($order, $context);
        $cart->add($lineItem);

        $recalculatedCart = $this->recalculateCart($cart, $salesChannelContext);

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(false)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();
        $orderData['stateId'] = $order->getStateId();

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderData): void {
            $this->orderRepository->upsert([$orderData], $context);
        });
    }

    public function addPromotionLineItem(string $orderId, string $code, Context $context): Cart
    {
        $order = $this->fetchOrder($orderId, $context);

        $this->validateOrder($order, $orderId);
        \assert($order instanceof OrderEntity);

        $options = [
            SalesChannelContextService::PERMISSIONS => OrderConverter::ADMIN_EDIT_ORDER_PERMISSIONS,
        ];

        $options[SalesChannelContextService::PERMISSIONS] = \array_merge(
            OrderConverter::ADMIN_EDIT_ORDER_PERMISSIONS,
            [
                PromotionCollector::SKIP_PROMOTION => false,
                PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS => true,
            ]
        );

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext(
            $order,
            $context,
            $options,
        );
        $cart = $this->orderConverter->convertToCart($order, $context);

        $promotionLineItem = $this->promotionItemBuilder->buildPlaceholderItem($code);

        $cart->add($promotionLineItem);
        $recalculatedCart = $this->recalculateCart($cart, $salesChannelContext);

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(false)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();
        $orderData['stateId'] = $order->getStateId();

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderData): void {
            $this->orderRepository->upsert([$orderData], $context);
        });

        return $recalculatedCart;
    }

    public function toggleAutomaticPromotion(string $orderId, Context $context, bool $skipAutomaticPromotions = true): Cart
    {
        $order = $this->fetchOrder($orderId, $context);

        $this->validateOrder($order, $orderId);
        \assert($order instanceof OrderEntity);

        $options = [
            SalesChannelContextService::PERMISSIONS => OrderConverter::ADMIN_EDIT_ORDER_PERMISSIONS,
        ];

        $options[SalesChannelContextService::PERMISSIONS] = \array_merge(
            OrderConverter::ADMIN_EDIT_ORDER_PERMISSIONS,
            [
                PromotionCollector::SKIP_PROMOTION => false,
                PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS => $skipAutomaticPromotions,
            ]
        );

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext(
            $order,
            $context,
            $options,
        );

        $cart = $this->orderConverter->convertToCart($order, $context);

        $recalculatedCart = $this->recalculateCart($cart, $salesChannelContext);

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(!$skipAutomaticPromotions)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();
        $orderData['stateId'] = $order->getStateId();

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderData): void {
            $this->orderRepository->upsert([$orderData], $context);
        });

        return $recalculatedCart;
    }

    /**
     * @throws AddressNotFoundException
     * @throws OrderException
     * @throws InconsistentCriteriaIdsException
     */
    public function replaceOrderAddressWithCustomerAddress(string $orderAddressId, string $customerAddressId, Context $context): void
    {
        $this->validateOrderAddress($orderAddressId, $context);

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('customer_address.id', $customerAddressId));

        $customerAddress = $this->customerAddressRepository->search($criteria, $context)->get($customerAddressId);
        if (!$customerAddress instanceof CustomerAddressEntity) {
            throw CartException::addressNotFound($customerAddressId);
        }

        $newOrderAddress = AddressTransformer::transform($customerAddress);
        $newOrderAddress['id'] = $orderAddressId;
        $this->orderAddressRepository->upsert([$newOrderAddress], $context);
    }

    private function addProductToDeliveryPosition(LineItem $item, Cart $cart): void
    {
        if ($cart->getDeliveries()->count() <= 0) {
            return;
        }

        /** @var Delivery $delivery */
        $delivery = $cart->getDeliveries()->first();
        if (!$delivery) {
            return;
        }

        $calculatedPrice = $item->getPrice();
        \assert($calculatedPrice instanceof CalculatedPrice);

        $position = new DeliveryPosition($item->getId(), clone $item, $item->getQuantity(), $calculatedPrice, $delivery->getDeliveryDate());

        $delivery->getPositions()->add($position);
    }

    private function fetchOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('lineItems.downloads')
            ->addAssociation('transactions.stateMachineState')
            ->addAssociation('deliveries.shippingMethod.tax')
            ->addAssociation('deliveries.shippingMethod.deliveryTime')
            ->addAssociation('deliveries.positions.orderLineItem')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('deliveries.shippingOrderAddress.countryState');

        return $this->orderRepository->search($criteria, $context)->getEntities()->get($orderId);
    }

    /**
     * @throws OrderException
     */
    private function validateOrder(?OrderEntity $order, string $orderId): void
    {
        if (!$order) {
            throw CartException::orderNotFound($orderId);
        }

        $this->checkVersion($order);
    }

    /**
     * @throws ProductNotFoundException
     * @throws InconsistentCriteriaIdsException
     */
    private function validateProduct(string $productId, Context $context): void
    {
        $product = $this->productRepository->search(new Criteria([$productId]), $context)->get($productId);

        if (!$product) {
            throw CartException::productNotFound($productId);
        }
    }

    private function checkVersion(Entity $entity): void
    {
        if ($entity->getVersionId() === Defaults::LIVE_VERSION) {
            throw OrderException::canNotRecalculateLiveVersion($entity->getUniqueIdentifier());
        }
    }

    /**
     * @throws AddressNotFoundException
     * @throws OrderException
     * @throws InconsistentCriteriaIdsException
     */
    private function validateOrderAddress(string $orderAddressId, Context $context): void
    {
        $address = $this->orderAddressRepository->search(new Criteria([$orderAddressId]), $context)->get($orderAddressId);
        if (!$address) {
            throw CartException::addressNotFound($orderAddressId);
        }

        $this->checkVersion($address);
    }

    private function recalculateCart(Cart $cart, SalesChannelContext $context): Cart
    {
        // we switch to the live version that we don't have to consider live version fallbacks inside the calculation
        return $context->live(function ($live) use ($cart): Cart {
            $behavior = new CartBehavior($live->getPermissions(), true, true);

            // all prices are now prepared for calculation - starts the cart calculation
            $cart = $this->processor->process($cart, $live, $behavior);

            // validate cart against the context rules
            $validated = $this->cartRuleLoader->loadByCart($live, $cart, $behavior);

            return $validated->getCart();
        });
    }
}
