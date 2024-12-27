<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use Cicada\Core\System\StateMachine\Transition;
use Cicada\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Cicada\Storefront\Page\Account\Order\AccountEditOrderPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class EditOrderPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    /**
     * @var EntityRepository<PaymentMethodCollection>
     */
    private EntityRepository $paymentMethodRepository;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = static::getContainer()->get('payment_method.repository');
    }

    public function testEditOrderPageShouldLoad(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);

        $event = null;
        $this->catchEvent(AccountEditOrderPageLoadedEvent::class, $event);

        $request->request->set('orderId', $orderId);
        $page = $this->getPageLoader()->load($request, $context);

        self::assertPageEvent(AccountEditOrderPageLoadedEvent::class, $event, $context, $request, $page);

        static::assertSame($orderId, $page->getOrder()->getId());
    }

    public function testEditOrderPageCorrectPayment(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);

        $event = null;
        $this->catchEvent(AccountEditOrderPageLoadedEvent::class, $event);

        $request->request->set('orderId', $orderId);
        $page = $this->getPageLoader()->load($request, $context);

        self::assertPageEvent(AccountEditOrderPageLoadedEvent::class, $event, $context, $request, $page);

        static::assertCount(1, $page->getPaymentMethods());

        // set Payment active to false and assert it will not be loaded
        $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
        $paymentMethod = $this->paymentMethodRepository->search($criteria, $context->getContext())->getEntities()->first();
        static::assertNotNull($paymentMethod);

        $this->paymentMethodRepository->update(
            [
                ['id' => $paymentMethod->getId(), 'active' => false],
            ],
            $context->getContext()
        );

        $request->request->set('orderId', $orderId);
        $page = $this->getPageLoader()->load($request, $context);

        self::assertPageEvent(AccountEditOrderPageLoadedEvent::class, $event, $context, $request, $page);

        static::assertCount(0, $page->getPaymentMethods());
    }

    public function testEditPageNotAvailableOrderIsPaid(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);
        $this->setOrderToTransactionState($orderId, $context, StateMachineTransitionActions::ACTION_PAID);

        $this->expectException(OrderException::class);

        $request->request->set('orderId', $orderId);
        $this->getPageLoader()->load($request, $context);
    }

    public function testShouldOnlyShowAvailablePaymentsForOrder(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);

        // Get customer from USA rule
        $ruleCriteria = new Criteria();
        $ruleCriteria->addFilter(new EqualsFilter('name', 'Customers from USA'));

        /** @var EntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = static::getContainer()->get('rule.repository');

        $ruleId = $ruleRepository->search($ruleCriteria, $context->getContext())->getEntities()->first()?->getId();
        static::assertNotNull($ruleId);

        $this->createCustomPaymentWithRule($context, $ruleId);

        // Fake context rules for USA customers
        $context->setRuleIds(array_merge($context->getRuleIds(), [$ruleId]));

        $page = $this->getPageLoader()->load($request, $context);

        static::assertSame($orderId, $page->getOrder()->getId());
        static::assertCount(1, $page->getPaymentMethods());
    }

    public function testShouldSortAvailablePaymentMethodsByPreference(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->placeRandomOrder($context);

        $primaryMethod = $this->createCustomPaymentMethod($context, ['position' => 1]);

        // create some dummy methods to test sorting
        $this->createCustomPaymentMethod($context, ['position' => 0]);
        $this->createCustomPaymentMethod($context, ['position' => 4]);

        if (Feature::isActive('ACCESSIBILITY_TWEAKS')) {
            $context->getSalesChannel()->setPaymentMethodId($primaryMethod->getId());
        } else {
            // replace active payment method with a new one
            $context->assign(['paymentMethod' => $primaryMethod]);
        }

        $page = $this->getPageLoader()->load($request, $context);
        $paymentMethods = \array_values($page->getPaymentMethods()->getElements());

        static::assertSame($primaryMethod->getId(), $paymentMethods[0]->getId());
    }

    public function testShouldNotAllowPaymentMethodChangeOnCertainTransactionStates(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertTrue($page->isPaymentChangeable());

        $this->setOrderToTransactionState(
            $orderId,
            $context,
            StateMachineTransitionActions::ACTION_AUTHORIZE
        );

        $page = $this->getPageLoader()->load($request, $context);

        static::assertFalse($page->isPaymentChangeable());
    }

    protected function getPageLoader(): AccountEditOrderPageLoader
    {
        return static::getContainer()->get(AccountEditOrderPageLoader::class);
    }

    private function setOrderToTransactionState(
        string $orderId,
        SalesChannelContext $context,
        string $transactionState
    ): void {
        $order = $this->getOrder($orderId, $context);

        $stateMachineRegistry = static::getContainer()->get(StateMachineRegistry::class);

        static::assertInstanceOf(OrderTransactionCollection::class, $order->getTransactions());

        $orderTransactionEntity = $order->getTransactions()->last();
        static::assertInstanceOf(OrderTransactionEntity::class, $orderTransactionEntity);

        $stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $orderTransactionEntity->getId(),
                $transactionState,
                'stateId'
            ),
            $context->getContext()
        );
    }

    private function getOrder(string $orderId, SalesChannelContext $context): OrderEntity
    {
        /** @var EntityRepository<OrderCollection> $orderRepository */
        $orderRepository = static::getContainer()->get('order.repository');
        $criteria = new Criteria([$orderId]);

        $criteria->addAssociations(['stateMachineState', 'transactions.stateMachineState']);

        $order = $orderRepository->search($criteria, $context->getContext())->getEntities()->first();
        static::assertNotNull($order);

        return $order;
    }

    private function createCustomPaymentWithRule(SalesChannelContext $context, string $ruleId): string
    {
        $paymentId = Uuid::randomHex();

        $this->paymentMethodRepository->create([
            [
                'id' => $paymentId,
                'name' => 'Test Payment with Rule',
                'technicalName' => Uuid::randomHex(),
                'description' => 'Payment rule test',
                'active' => true,
                'afterOrderEnabled' => true,
                'availabilityRuleId' => $ruleId,
                'salesChannels' => [
                    [
                        'id' => $context->getSalesChannelId(),
                    ],
                ],
            ],
        ], $context->getContext());

        return $paymentId;
    }

    /**
     * @param array<string, int> $options
     */
    private function createCustomPaymentMethod(SalesChannelContext $context, array $options): PaymentMethodEntity
    {
        $paymentId = Uuid::randomHex();

        $data = \array_replace_recursive(
            [
                'id' => $paymentId,
                'name' => 'Test Payment',
                'technicalName' => Uuid::randomHex(),
                'description' => 'Payment test',
                'active' => true,
                'afterOrderEnabled' => true,
                'salesChannels' => [
                    [
                        'id' => $context->getSalesChannelId(),
                    ],
                ],
            ],
            $options
        );

        $this->paymentMethodRepository->create([$data], $context->getContext());

        $paymentMethod = $this->paymentMethodRepository->search(new Criteria([$paymentId]), $context->getContext())->getEntities()->first();
        static::assertNotNull($paymentMethod);

        return $paymentMethod;
    }
}
