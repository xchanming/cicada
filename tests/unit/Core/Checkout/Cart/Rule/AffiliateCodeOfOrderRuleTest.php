<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Rule\AffiliateCodeOfOrderRule;
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
#[Package('fundamentals@after-sales')]
#[CoversClass(AffiliateCodeOfOrderRule::class)]
#[Group('rules')]
class AffiliateCodeOfOrderRuleTest extends TestCase
{
    public function testGetConstraints(): void
    {
        $constraints = (new AffiliateCodeOfOrderRule())->getConstraints();

        static::assertArrayHasKey('affiliateCode', $constraints, 'Constraint affiliateCode not found in Rule');
        static::assertEquals($constraints['affiliateCode'], [
            new NotBlank(),
            new Type(['type' => 'string']),
        ]);
    }

    public function testName(): void
    {
        $rule = new AffiliateCodeOfOrderRule();
        static::assertSame('orderAffiliateCode', $rule->getName());
    }

    public function testGetConfig(): void
    {
        $config = (new AffiliateCodeOfOrderRule())->getConfig();
        static::assertEquals([
            'fields' => [
                'affiliateCode' => [
                    'name' => 'affiliateCode',
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

    public function testMatchWithWrongRuleScope(): void
    {
        $scope = $this->createMock(TestRuleScope::class);

        $match = (new AffiliateCodeOfOrderRule())->match($scope);

        static::assertFalse($match);
    }

    public function testInvalidCombinationOfValueAndOperator(): void
    {
        $this->expectException(CartException::class);
        $rule = new AffiliateCodeOfOrderRule(Rule::OPERATOR_EQ, null);

        $order = new OrderEntity();
        $order->setAffiliateCode('TestAffiliateCode123');
        $cart = new Cart('ABC');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $scope = new FlowRuleScope($order, $cart, $salesChannelContext);
        $rule->match($scope);
    }

    #[DataProvider('getCaseTestMatchValues')]
    public function testMatch(string $operator, ?string $ruleCode, ?string $orderAffiliateCode, bool $isMatching): void
    {
        $rule = new AffiliateCodeOfOrderRule($operator, $ruleCode);

        $order = new OrderEntity();
        $order->setAffiliateCode($orderAffiliateCode);
        $cart = new Cart('ABC');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);

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
            'orderAffiliateCode' => 'testingCode',
            'isMatching' => true,
        ];

        yield 'Equals Operator is not matching' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleCode' => 'testingCode',
            'orderAffiliateCode' => 'otherCode',
            'isMatching' => false,
        ];

        yield 'Not Equals Operator is matching' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleCode' => 'testingCode',
            'orderAffiliateCode' => 'otherCode',
            'isMatching' => true,
        ];

        yield 'Not Equals Operator is not matching' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleCode' => 'testingCode',
            'orderAffiliateCode' => 'testingCode',
            'isMatching' => false,
        ];

        yield 'Empty Operator is matching, because both codes does not exist' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => null,
            'orderAffiliateCode' => null,
            'isMatching' => true,
        ];

        yield 'Empty Operator is matching, because cart code does not exist' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => 'testingCode',
            'orderAffiliateCode' => null,
            'isMatching' => true,
        ];

        yield 'Empty Operator is not matching, because both codes are filled' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => 'testingCode',
            'orderAffiliateCode' => 'testingCode',
            'isMatching' => false,
        ];

        yield 'Empty Operator is not matching, because cart code is filled' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleCode' => null,
            'orderAffiliateCode' => 'testingCode',
            'isMatching' => false,
        ];
    }
}
