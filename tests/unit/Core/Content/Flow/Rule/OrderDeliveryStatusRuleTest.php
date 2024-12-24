<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Rule\FlowRuleScope;
use Cicada\Core\Content\Flow\Rule\OrderDeliveryStatusRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(OrderDeliveryStatusRule::class)]
#[Group('rules')]
class OrderDeliveryStatusRuleTest extends TestCase
{
    private OrderDeliveryStatusRule $rule;

    protected function setUp(): void
    {
        $this->rule = new OrderDeliveryStatusRule();
    }

    public function testName(): void
    {
        static::assertSame('orderDeliveryStatus', $this->rule->getName());
    }

    public function testConstraints(): void
    {
        $constraints = $this->rule->getConstraints();

        static::assertArrayHasKey('stateIds', $constraints, 'stateIds constraint not found');
        static::assertArrayHasKey('operator', $constraints, 'operator constraint not found');

        static::assertEquals(RuleConstraints::uuids(), $constraints['stateIds']);
        static::assertEquals(RuleConstraints::uuidOperators(false), $constraints['operator']);
    }

    /**
     * @param list<string> $selectedOrderStateIds
     */
    #[DataProvider('getMatchingValues')]
    public function testOrderDeliveryStatusRuleMatching(bool $expected, string $orderStateId, array $selectedOrderStateIds, string $operator): void
    {
        $orderDeliveryCollection = new OrderDeliveryCollection();
        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId(Uuid::randomHex());
        $orderDelivery->setStateId($orderStateId);
        $orderDeliveryCollection->add($orderDelivery);
        $order = new OrderEntity();
        $order->setDeliveries($orderDeliveryCollection);
        $scope = new FlowRuleScope(
            $order,
            new Cart('test'),
            $this->createMock(SalesChannelContext::class)
        );

        $this->rule->assign(['stateIds' => $selectedOrderStateIds, 'operator' => $operator]);
        static::assertSame($expected, $this->rule->match($scope));
    }

    public function testInvalidScopeIsFalse(): void
    {
        $invalidScope = $this->createMock(RuleScope::class);
        $this->rule->assign(['salutationIds' => [Uuid::randomHex()], 'operator' => Rule::OPERATOR_EQ]);
        static::assertFalse($this->rule->match($invalidScope));
    }

    public function testDeliveriesEmpty(): void
    {
        $order = new OrderEntity();
        $order->setDeliveries(new OrderDeliveryCollection());
        $orderDeliveryCollection = new OrderDeliveryCollection();
        $order->setDeliveries($orderDeliveryCollection);
        $scope = new FlowRuleScope(
            $order,
            new Cart('test'),
            $this->createMock(SalesChannelContext::class)
        );

        $this->rule->assign(['stateIds' => [Uuid::randomHex()], 'operator' => Rule::OPERATOR_EQ]);
        static::assertFalse($this->rule->match($scope));
    }

    public function testConfig(): void
    {
        $config = (new OrderDeliveryStatusRule())->getConfig();
        $configData = $config->getData();

        static::assertArrayHasKey('operatorSet', $configData);
        $operators = RuleConfig::OPERATOR_SET_STRING;

        static::assertEquals([
            'operators' => $operators,
            'isMatchAny' => true,
        ], $configData['operatorSet']);
    }

    /**
     * @return array<string, array{bool, string, list<string>, string}>
     */
    public static function getMatchingValues(): array
    {
        $id = Uuid::randomHex();

        return [
            'ONE OF - true' => [true, $id, [$id, Uuid::randomHex()], Rule::OPERATOR_EQ],
            'ONE OF - false' => [false, $id, [Uuid::randomHex()], Rule::OPERATOR_EQ],
            'NONE OF - true' => [true, $id, [Uuid::randomHex()], Rule::OPERATOR_NEQ],
            'NONE OF - false' => [false, $id, [$id, Uuid::randomHex()], Rule::OPERATOR_NEQ],
        ];
    }
}
