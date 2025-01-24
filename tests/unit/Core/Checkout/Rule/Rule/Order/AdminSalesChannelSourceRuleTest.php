<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Order;

use Cicada\Core\Checkout\Cart\Rule\AdminSalesChannelSourceRule;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Cicada\Tests\Unit\Core\Checkout\Customer\Rule\TestRuleScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(AdminSalesChannelSourceRule::class)]
#[Group('rules')]
class AdminSalesChannelSourceRuleTest extends TestCase
{
    private AdminSalesChannelSourceRule $rule;

    protected function setUp(): void
    {
        $this->rule = new AdminSalesChannelSourceRule();
    }

    public function testGetName(): void
    {
        static::assertEquals('adminSalesChannelSource', $this->rule->getName());
    }

    public function testRuleConfig(): void
    {
        $config = $this->rule->getConfig();
        static::assertEquals([
            'fields' => [
                'hasAdminSalesChannelSource' => [
                    'name' => 'hasAdminSalesChannelSource',
                    'type' => 'bool',
                    'config' => [],
                ],
            ],
            'operatorSet' => null,
        ], $config->getData());
    }

    public function testGetConstraints(): void
    {
        $rule = new AdminSalesChannelSourceRule();
        $constraints = $rule->getConstraints();

        static::assertArrayHasKey('hasAdminSalesChannelSource', $constraints, 'Constraint hasAdminSalesChannelSource not found in Rule');
        static::assertEquals($constraints['hasAdminSalesChannelSource'], [
            new Type(['type' => 'bool']),
        ]);
    }

    public function testMatchWithWrongRuleScope(): void
    {
        $scope = new TestRuleScope(Generator::generateSalesChannelContext());

        $match = $this->rule->match($scope);

        static::assertFalse($match);
    }

    #[DataProvider('getCaseTestMatchValues')]
    public function testMatch(AdminSalesChannelSourceRule $rule, SalesChannelContext $context, bool $isMatching): void
    {
        $scope = new CheckoutRuleScope($context);

        $match = $rule->match($scope);
        static::assertEquals($match, $isMatching);
    }

    public static function getCaseTestMatchValues(): \Generator
    {
        $contextAdminSource = new AdminSalesChannelApiSource(
            'test-sales-channel-id',
            new Context(new AdminApiSource(null))
        );

        yield 'Condition is not processed by Admin SalesChannel source => Does not match because the order is processed by Admin SalesChannel source' => [
            new AdminSalesChannelSourceRule(false),
            Generator::generateSalesChannelContext(new Context($contextAdminSource)),
            false,
        ];

        yield 'Condition is processed by Admin SalesChannel source => Matches because the order is processed by Admin SalesChannel source' => [
            new AdminSalesChannelSourceRule(true),
            Generator::generateSalesChannelContext(new Context($contextAdminSource)),
            true,
        ];

        yield 'Condition is processed by Admin SalesChannel source => Does not match because the order is not processed by Admin SalesChannel source' => [
            new AdminSalesChannelSourceRule(true),
            Generator::generateSalesChannelContext(),
            false,
        ];

        yield 'Condition is not processed by Admin SalesChannel source => Matches because the order is not processed by Admin SalesChannel source' => [
            new AdminSalesChannelSourceRule(false),
            Generator::generateSalesChannelContext(),
            true,
        ];
    }
}
