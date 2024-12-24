<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Rule\CampaignCodeOfOrderRule;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Rule\FlowRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Customer\Rule\TestRuleScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CampaignCodeOfOrderRule::class)]
#[Group('rules')]
class CampaignCodeOfOrderRuleTest extends TestCase
{
    public function testGetConstraints(): void
    {
        $constraints = (new CampaignCodeOfOrderRule())->getConstraints();

        static::assertArrayHasKey('campaignCode', $constraints, 'Constraint campaign not found in Rule');
        static::assertEquals($constraints['campaignCode'], [
            new NotBlank(),
            new Type(['type' => 'string']),
        ]);
    }

    public function testName(): void
    {
        $rule = new CampaignCodeOfOrderRule();
        static::assertSame('orderCampaignCode', $rule->getName());
    }

    public function testGetConfig(): void
    {
        $config = (new CampaignCodeOfOrderRule())->getConfig();
        static::assertEquals([
            'fields' => [
                'campaignCode' => [
                    'name' => 'campaignCode',
                    'type' => 'string',
                    'config' => [],
                ],
            ],
            'operatorSet' => [
                'operators' => [Rule::OPERATOR_EQ, Rule::OPERATOR_NEQ, Rule::OPERATOR_EMPTY],
                'isMatchAny' => false,
            ],
        ], $config->getData());
    }

    public function testInvalidCombinationOfValueAndOperator(): void
    {
        $this->expectException(CartException::class);
        $rule = new CampaignCodeOfOrderRule(Rule::OPERATOR_EQ, null);

        $cart = new Cart('ABC');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $order = new OrderEntity();
        $order->setCampaignCode('TestCampaignCode123');
        $scope = new FlowRuleScope($order, $cart, $salesChannelContext);
        $rule->match($scope);
    }

    public function testMatchWithWrongRuleScope(): void
    {
        $scope = $this->createMock(TestRuleScope::class);

        $match = (new CampaignCodeOfOrderRule())->match($scope);

        static::assertFalse($match);
    }

    #[DataProvider('getCaseTestMatchValues')]
    public function testMatch(string $operator, ?string $ruleCode, ?string $orderCampaignCode, bool $isMatching): void
    {
        $rule = new CampaignCodeOfOrderRule($operator, $ruleCode);
        $cart = new Cart('ABC');
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $order = new OrderEntity();
        $order->setCampaignCode($orderCampaignCode);
        $scope = new FlowRuleScope($order, $cart, $salesChannelContext);
        $match = $rule->match($scope);
        static::assertEquals($match, $isMatching);
    }

    /**
     * @return \Traversable<array<mixed>>
     */
    public static function getCaseTestMatchValues(): \Traversable
    {
        yield 'Equals Operator is matching' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => 'testingCode',
            'isMatching' => true,
        ];

        yield 'Equals Operator is not matching' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => 'otherCode',
            'isMatching' => false,
        ];

        yield 'Not Equals Operator is matching' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => 'otherCode',
            'isMatching' => true,
        ];

        yield 'Not Equals Operator is not matching' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => 'testingCode',
            'isMatching' => false,
        ];

        yield 'Empty Operator is matching, because both codes does not exist' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => null,
            'orderCampaignCode' => null,
            'isMatching' => true,
        ];

        yield 'Empty Operator is matching, because cart code does not exist' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => null,
            'isMatching' => true,
        ];

        yield 'Empty Operator is not matching, because both codes are filled' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => 'testingCode',
            'orderCampaignCode' => 'testingCode',
            'isMatching' => false,
        ];

        yield 'Empty Operator is not matching, because cart code is filled' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => null,
            'orderCampaignCode' => 'testingCode',
            'isMatching' => false,
        ];
    }
}
