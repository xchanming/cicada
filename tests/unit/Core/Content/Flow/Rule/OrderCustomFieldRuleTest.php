<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Rule\FlowRuleScope;
use Cicada\Core\Content\Flow\Rule\OrderCustomFieldRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;
use Cicada\Tests\Unit\Core\Checkout\Customer\Rule\TestRuleScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(OrderCustomFieldRule::class)]
#[Group('rules')]
class OrderCustomFieldRuleTest extends TestCase
{
    use CartRuleHelperTrait;

    private const CUSTOM_FIELD_NAME = 'custom_test';

    private OrderCustomFieldRule $rule;

    private OrderEntity $order;

    protected function setUp(): void
    {
        $this->rule = new OrderCustomFieldRule();

        $this->order = new OrderEntity();
    }

    public function testGetName(): void
    {
        static::assertSame('orderCustomField', $this->rule->getName());
    }

    public function testMatchWithWrongRuleScope(): void
    {
        $scope = $this->createMock(TestRuleScope::class);

        $match = $this->rule->match($scope);

        static::assertFalse($match);
    }

    public function testGetConstraintsWithRenderedField(): void
    {
        $this->rule->assign([
            'renderedField' => [
                'type' => 'string',
            ],
        ]);

        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('renderedFieldValue', $ruleConstraints, 'Rule Constraint renderedFieldValue is not defined');
    }

    public function testMatch(): void
    {
        $this->order->assign(['customFields' => [
            self::CUSTOM_FIELD_NAME => 'my_invalid_value',
        ]]);

        $scope = new FlowRuleScope($this->order, new Cart('test'), $this->createMock(SalesChannelContext::class));

        $this->rule->assign(
            [
                'operator' => '=',
                'renderedField' => [
                    'type' => 'string',
                    'name' => self::CUSTOM_FIELD_NAME,
                ],
                'renderedFieldValue' => 'my_test_value',
            ]
        );

        static::assertFalse($this->rule->match($scope));
    }
}
