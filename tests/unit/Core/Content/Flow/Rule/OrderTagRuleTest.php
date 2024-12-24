<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Rule\FlowRuleScope;
use Cicada\Core\Content\Flow\Rule\OrderTagRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Tag\TagCollection;
use Cicada\Core\System\Tag\TagEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(OrderTagRule::class)]
#[Group('rules')]
class OrderTagRuleTest extends TestCase
{
    private OrderTagRule $rule;

    protected function setUp(): void
    {
        $this->rule = new OrderTagRule();
    }

    public function testGetName(): void
    {
        static::assertEquals('orderTag', $this->rule->getName());
    }

    public function testRuleConfig(): void
    {
        $expectedConfiguration = [
            'operatorSet' => [
                'operators' => [
                    Rule::OPERATOR_EQ,
                    Rule::OPERATOR_NEQ,
                    Rule::OPERATOR_EMPTY,
                ],
                'isMatchAny' => 1,
            ],
            'fields' => [
                'identifiers' => [
                    'name' => 'identifiers',
                    'type' => 'multi-entity-id-select',
                    'config' => [
                        'entity' => 'tag',
                    ],
                ],
            ],
        ];

        $data = $this->rule->getConfig()->getData();
        static::assertEquals($expectedConfiguration, $data);
    }

    public function testConstraints(): void
    {
        $operators = [
            Rule::OPERATOR_EQ,
            Rule::OPERATOR_NEQ,
            Rule::OPERATOR_EMPTY,
        ];

        $constraints = $this->rule->getConstraints();

        static::assertArrayHasKey('identifiers', $constraints, 'identifiers constraint not found');
        static::assertArrayHasKey('operator', $constraints, 'operator constraints not found');

        static::assertEquals([new NotBlank(), new ArrayOfUuid()], $constraints['identifiers']);
        static::assertEquals([new NotBlank(), new Choice($operators)], $constraints['operator']);
    }

    /**
     * @param array<string>|string|null $givenIdentifier
     * @param array<string> $ruleIdentifiers
     */
    #[DataProvider('getMatchValues')]
    public function testRuleMatching(string $operator, bool $isMatching, array $ruleIdentifiers, $givenIdentifier): void
    {
        $order = new OrderEntity();
        $tagCollection = new TagCollection();
        $orderTagIds = array_filter(\is_array($givenIdentifier) ? $givenIdentifier : [$givenIdentifier]);
        foreach ($orderTagIds as $orderTagId) {
            $tag = new TagEntity();
            $tag->setId($orderTagId);
            $tagCollection->add($tag);
        }
        $order->setTags($tagCollection);

        $scope = new FlowRuleScope(
            $order,
            new Cart('test'),
            $this->createMock(SalesChannelContext::class)
        );

        $this->rule->assign(['identifiers' => $ruleIdentifiers, 'operator' => $operator]);

        $match = $this->rule->match($scope);
        if ($isMatching) {
            static::assertTrue($match);
        } else {
            static::assertFalse($match);
        }
    }

    public static function getMatchValues(): \Generator
    {
        yield 'operator_eq / not match / identifier' => [Rule::OPERATOR_EQ, false, ['kyln123', 'kyln456'], 'kyln000'];
        yield 'operator_eq / match partly / identifier' => [Rule::OPERATOR_EQ, true, ['kyln123', 'kyln456'], 'kyln123'];
        yield 'operator_eq / match full / identifier' => [Rule::OPERATOR_EQ, true, ['kyln123', 'kyln456'], ['kyln123', 'kyln456']];
        yield 'operator_neq / match / identifier' => [Rule::OPERATOR_NEQ, true, ['kyln123', 'kyln456'], 'kyln000'];
        yield 'operator_neq / not match / identifier' => [Rule::OPERATOR_NEQ, false, ['kyln123', 'kyln456'], 'kyln123'];
        yield 'operator_empty / not match / identifier' => [Rule::OPERATOR_NEQ, false, ['kyln123', 'kyln456'], 'kyln123'];
        yield 'operator_empty / match / identifier' => [Rule::OPERATOR_EMPTY, true, ['kyln123', 'kyln456'], null];
    }

    public function testNotMatchingWithUnsupportedScope(): void
    {
        $scope = $this->createMock(CartRuleScope::class);

        static::assertFalse($this->rule->match($scope));
    }
}
