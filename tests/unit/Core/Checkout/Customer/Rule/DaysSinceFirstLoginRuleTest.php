<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\DaysSinceFirstLoginRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\DaysSinceRule;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(DaysSinceFirstLoginRule::class)]
#[CoversClass(DaysSinceRule::class)]
#[Group('rules')]
class DaysSinceFirstLoginRuleTest extends TestCase
{
    protected DaysSinceFirstLoginRule $rule;

    protected function setUp(): void
    {
        $this->rule = new DaysSinceFirstLoginRule();
    }

    public function testGetName(): void
    {
        static::assertSame('customerDaysSinceFirstLogin', $this->rule->getName());
    }

    public function testInvalidCombinationOfValueAndOperator(): void
    {
        $this->expectException(UnsupportedValueException::class);
        $this->rule->assign([
            'operator' => Rule::OPERATOR_EQ,
            'daysPassed' => null,
        ]);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();
        $salesChannelContext->method('getCustomer')->willReturn($customer);

        $this->rule->match(new CheckoutRuleScope($salesChannelContext));
    }

    #[DataProvider('getCaseTestMatchValues')]
    public function testIfMatchesCorrect(
        string $operator,
        bool $isMatching,
        float $daysPassed,
        ?\DateTimeImmutable $day,
        bool $noCustomer = false
    ): void {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $customer = new CustomerEntity();
        $customer->setFirstLogin($day);

        if ($noCustomer) {
            $customer = null;
        }
        $salesChannelContext->method('getCustomer')->willReturn($customer);
        $scope = $this->createMock(CheckoutRuleScope::class);
        $scope->method('getSalesChannelContext')->willReturn($salesChannelContext);
        $scope->method('getCurrentTime')->willReturn(self::getTestTimestamp());

        $this->rule->assign([
            'operator' => $operator,
            'daysPassed' => $daysPassed,
        ]);

        $match = $this->rule->match($scope);

        if ($isMatching) {
            static::assertTrue($match);
        } else {
            static::assertFalse($match);
        }
    }

    /**
     * @return \Traversable<list<mixed>>
     */
    public static function getCaseTestMatchValues(): \Traversable
    {
        $datetime = self::getTestTimestamp();

        $dayTest = $datetime->modify('-30 minutes');

        yield 'operator_eq / not match / day passed / day' => [Rule::OPERATOR_EQ, false, 1.2, $dayTest];
        yield 'operator_eq / match / day passed / day' => [Rule::OPERATOR_EQ, true, 0, $dayTest];
        yield 'operator_neq / match / day passed / day' => [Rule::OPERATOR_NEQ, true, 1, $dayTest];
        yield 'operator_neq / not match / day passed/ day' => [Rule::OPERATOR_NEQ, false, 0, $dayTest];
        yield 'operator_lte_lt / not match / day passed / day' => [Rule::OPERATOR_LTE, false, -1.1, $dayTest];
        yield 'operator_lte_lt / match / day passed/ day' => [Rule::OPERATOR_LTE, true, 1,  $dayTest];
        yield 'operator_lte_e / match / day passed/ day' => [Rule::OPERATOR_LTE, true, 0, $dayTest];
        yield 'operator_gte_gt / not match / day passed/ day' => [Rule::OPERATOR_GTE, false, 1, $dayTest];
        yield 'operator_gte_gt / match / day passed / day' => [Rule::OPERATOR_GTE, true, -1, $dayTest];
        yield 'operator_gte_e / match / day passed / day' => [Rule::OPERATOR_GTE, true, 0, $dayTest];
        yield 'operator_lt / not match / day passed / day' => [Rule::OPERATOR_LT, false, 0, $dayTest];
        yield 'operator_lt / match / day passed / day' => [Rule::OPERATOR_LT, true, 1,  $dayTest];
        yield 'operator_gt / not match / day passed / day' => [Rule::OPERATOR_GT, false, 1, $dayTest];
        yield 'operator_gt / match / day passed / day' => [Rule::OPERATOR_GT, true, -1, $dayTest];
        yield 'operator_empty / not match / day passed/ day' => [Rule::OPERATOR_EMPTY, false, 0, $dayTest];
        yield 'operator_empty / match / day passed / day' => [Rule::OPERATOR_EMPTY, true, 0, null];
        yield 'operator_eq / no match / no customer' => [Rule::OPERATOR_EQ, false, 0, $dayTest, true];
        yield 'operator_neq / match / no customer' => [Rule::OPERATOR_NEQ, true, 0, $dayTest, true];
        yield 'operator_empty / match / no customer' => [Rule::OPERATOR_EMPTY, true, 0, $dayTest, true];
    }

    private static function getTestTimestamp(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2020-03-10T15:00:00+00:00');
    }
}
