<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\DaysSinceLastOrderRule;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use Cicada\Core\System\StateMachine\Transition;
use Cicada\Core\Test\Integration\Traits\OrderFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(DaysSinceLastOrderRule::class)]
#[Group('rules')]
class DaysSinceLastOrderRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderFixture;

    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    private StateMachineRegistry $stateMachineRegistry;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
        $this->stateMachineRegistry = static::getContainer()->get(StateMachineRegistry::class);
    }

    public function testValidateWithMissingValues(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new DaysSinceLastOrderRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(2, $exceptions);
            static::assertSame('/0/value/daysPassed', $exceptions[1]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[1]['code']);

            static::assertSame('/0/value/operator', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);
        }
    }

    public function testValidateWithInvalidValue(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new DaysSinceLastOrderRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'daysPassed' => false,
                        'operator' => DaysSinceLastOrderRule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(2, $exceptions);
            static::assertSame('/0/value/daysPassed', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);

            static::assertSame('/0/value/daysPassed', $exceptions[1]['source']['pointer']);
            static::assertSame(Type::INVALID_TYPE_ERROR, $exceptions[1]['code']);
        }
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new DaysSinceLastOrderRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'daysPassed' => 10.1,
                    'operator' => DaysSinceLastOrderRule::OPERATOR_EQ,
                ],
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }

    public function testWithRealCustomerEntity(): void
    {
        $scope = $this->createRealTestScope();

        $rule = new DaysSinceLastOrderRule();
        $rule->assign(['daysPassed' => 1, 'operator' => Rule::OPERATOR_EQ]);

        static::assertFalse($rule->match($scope));
    }

    public function testCustomerMetaFieldSubscriber(): void
    {
        /** @var EntityRepository $orderRepository */
        $orderRepository = static::getContainer()->get('order.repository');
        /** @var EntityRepository $customerRepository */
        $customerRepository = static::getContainer()->get('customer.repository');
        $defaultContext = Context::createDefaultContext();
        $orderId = Uuid::randomHex();
        $orderData = $this->getOrderData($orderId, $defaultContext);

        $orderRepository->create($orderData, $defaultContext);

        $this->stateMachineRegistry->transition(
            new Transition(
                'order',
                $orderId,
                StateMachineTransitionActions::ACTION_PROCESS,
                'stateId',
            ),
            $this->context
        );

        $this->stateMachineRegistry->transition(
            new Transition(
                'order',
                $orderId,
                StateMachineTransitionActions::ACTION_COMPLETE,
                'stateId',
            ),
            $this->context
        );

        /** @var CustomerCollection|CustomerEntity[] $result */
        $result = $customerRepository->search(
            new Criteria([$orderData[0]['orderCustomer']['customer']['id']]),
            $defaultContext
        );

        static::assertNotNull($result->first());
        static::assertSame(1, $result->first()->getOrderCount());
        static::assertNotNull($result->first()->getLastOrderDate());
    }

    private function createRealTestScope(): CheckoutRuleScope
    {
        $checkoutContext = $this->createMock(SalesChannelContext::class);
        $customer = $this->createTestOrderAndReturnCustomer();

        $checkoutContext->method('getCustomer')
            ->willReturn($customer);

        return new CheckoutRuleScope($checkoutContext);
    }

    private function createTestOrderAndReturnCustomer(): CustomerEntity
    {
        /** @var EntityRepository $customerRepository */
        $customerRepository = static::getContainer()->get('customer.repository');
        $orderRepository = static::getContainer()->get('order.repository');

        $orderId = Uuid::randomHex();
        $defaultContext = Context::createDefaultContext();

        $orderData = array_map(static function (array $order): array {
            $order['orderDateTime'] = new \DateTime('2020-03-10T15:00:00+00:00');

            return $order;
        }, $this->getOrderData($orderId, $defaultContext));

        $orderRepository->create($orderData, $defaultContext);
        $criteria = new Criteria([$orderData[0]['orderCustomer']['customer']['id']]);

        /** @var CustomerEntity $customer */
        $customer = $customerRepository->search($criteria, $defaultContext)->getEntities()->first();

        return $customer;
    }
}
