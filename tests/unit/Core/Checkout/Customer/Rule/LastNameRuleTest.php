<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\LastNameRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(LastNameRule::class)]
#[Group('rules')]
class LastNameRuleTest extends TestCase
{
    private LastNameRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LastNameRule();
    }

    public function testName(): void
    {
        static::assertSame('customerLastName', $this->rule->getName());
    }

    public function testConstraints(): void
    {
        $constraints = $this->rule->getConstraints();

        static::assertArrayHasKey('lastName', $constraints, 'LastName constraint not found');
        static::assertArrayHasKey('operator', $constraints, 'operator constraints not found');

        static::assertEquals(RuleConstraints::stringOperators(), $constraints['operator']);
        static::assertEquals(RuleConstraints::string(), $constraints['lastName']);
    }

    #[DataProvider('getMatchCustomerLastNameValues')]
    public function testLastNameRuleMatching(bool $expected, ?string $customerName, ?string $ruleNameValue, string $operator): void
    {
        $customer = new CustomerEntity();
        $customer->setLastName($customerName ?? '');

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        $cart = new Cart('test');
        $scope = new CartRuleScope($cart, $context);

        $this->rule->assign(['lastName' => $ruleNameValue, 'operator' => $operator]);

        $isMatching = $this->rule->match($scope);

        static::assertSame($expected, $isMatching);
    }

    public function testConfig(): void
    {
        $config = (new LastNameRule())->getConfig();
        $configData = $config->getData();

        static::assertArrayHasKey('operatorSet', $configData);
        $operators = RuleConfig::OPERATOR_SET_STRING;
        $operators[] = Rule::OPERATOR_EMPTY;

        static::assertEquals([
            'operators' => $operators,
            'isMatchAny' => false,
        ], $configData['operatorSet']);
    }

    public function testCustomerNotExist(): void
    {
        $scope = new CartRuleScope(
            new Cart('test'),
            $this->createMock(SalesChannelContext::class)
        );

        $this->rule->assign(['lastName' => 'cicada', 'operator' => Rule::OPERATOR_EQ]);
        static::assertFalse($this->rule->match($scope));
    }

    public function testCustomerNotExistAndOperatorEmpty(): void
    {
        $scope = new CartRuleScope(
            new Cart('test'),
            $this->createMock(SalesChannelContext::class)
        );

        $this->rule->assign(['lastName' => 'cicada', 'operator' => Rule::OPERATOR_EMPTY]);
        static::assertTrue($this->rule->match($scope));
    }

    public function testInvalidLastName(): void
    {
        $customer = new CustomerEntity();
        $customer->setLastName('cicada');

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        $cart = new Cart('test');
        $scope = new CartRuleScope($cart, $context);

        $this->rule->assign(['lastName' => true, 'operator' => Rule::OPERATOR_EQ]);

        $this->expectException(UnsupportedValueException::class);
        static::assertFalse($this->rule->match($scope));
    }

    public function testInvalidScopeIsFalse(): void
    {
        $invalidScope = $this->createMock(RuleScope::class);
        $this->rule->assign(['lastName' => 'cicada', 'operator' => Rule::OPERATOR_EQ]);
        static::assertFalse($this->rule->match($invalidScope));
    }

    /**
     * @return array<string, array{bool, string|null, string|null, string}>
     */
    public static function getMatchCustomerLastNameValues(): array
    {
        return [
            'EQ - true' => [true, 'cicada', 'cicada', Rule::OPERATOR_EQ],
            'EQ - false' => [false, 'cicada', 'cicadaAG', Rule::OPERATOR_EQ],
            'EQ(CASE) - true' => [true, 'cicada', 'ShopWare', Rule::OPERATOR_EQ],
            'NEQ - true' => [true, 'cicada', 'cicadaAG', Rule::OPERATOR_NEQ],
            'NEQ - false' => [false, 'cicada', 'cicada', Rule::OPERATOR_NEQ],
            'NEQ(CASE) - false' => [false, 'cicada', 'ShopWare', Rule::OPERATOR_NEQ],
            'EMPTY - false' => [false, 'cicada', null, Rule::OPERATOR_EMPTY],
            'EMPTY - true' => [true, null, null, Rule::OPERATOR_EMPTY],
        ];
    }
}
