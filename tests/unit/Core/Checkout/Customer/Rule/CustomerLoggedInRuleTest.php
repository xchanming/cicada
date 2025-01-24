<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\CustomerLoggedInRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(CustomerLoggedInRule::class)]
#[Group('rules')]
class CustomerLoggedInRuleTest extends TestCase
{
    public function testGetConstraints(): void
    {
        $rule = new CustomerLoggedInRule();
        $constraints = $rule->getConstraints();

        static::assertArrayHasKey('isLoggedIn', $constraints, 'Constraint isLoggedIn not found in Rule');
        static::assertEquals($constraints['isLoggedIn'], [
            new NotNull(),
            new Type(['type' => 'bool']),
        ]);
    }

    public function testName(): void
    {
        $rule = new CustomerLoggedInRule();
        static::assertSame('customerLoggedIn', $rule->getName());
    }

    public function testGetConfig(): void
    {
        $rule = new CustomerLoggedInRule();
        $config = $rule->getConfig();
        static::assertEquals([
            'fields' => [
                'isLoggedIn' => [
                    'name' => 'isLoggedIn',
                    'type' => 'bool',
                    'config' => [],
                ],
            ],
            'operatorSet' => null,
        ], $config->getData());
    }

    public function testMatchWithWrongRuleScope(): void
    {
        $rule = new CustomerLoggedInRule();

        $scope = $this->createMock(TestRuleScope::class);

        $match = $rule->match($scope);

        static::assertFalse($match);
    }

    #[DataProvider('getCaseTestMatchValues')]
    public function testMatch(bool $isLoggedIn, bool $hasCustomer, bool $isMatching): void
    {
        $rule = new CustomerLoggedInRule($isLoggedIn);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->expects(static::once())
            ->method('getCustomer')
            ->willReturn($hasCustomer ? new CustomerEntity() : null);

        $scope = new CheckoutRuleScope($salesChannelContext);
        $match = $rule->match($scope);
        static::assertEquals($match, $isMatching);
    }

    public static function getCaseTestMatchValues(): \Generator
    {
        yield 'Condition is not logged in => Not match because user logged in' => [
            false,
            true,
            false,
        ];

        yield 'Condition logged in => Not match because user not logged in' => [
            true,
            false,
            false,
        ];

        yield 'Condition is not logged in => Match because user not logged in' => [
            false,
            false,
            true,
        ];

        yield 'Condition is logged in => Match because user logged in' => [
            true,
            true,
            true,
        ];
    }
}
