<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Cart;

use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\CartVolumeRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CartVolumeRule::class)]
class CartVolumeRuleTest extends TestCase
{
    public function testMatchWithWrongScopeShouldReturnFalse(): void
    {
        $cartVolumeRule = new CartVolumeRule();

        $wrongScope = $this->createMock(RuleScope::class);

        static::assertFalse($cartVolumeRule->match($wrongScope));
    }

    public function testMatchWithNullVolumeShouldThrowException(): void
    {
        $cartVolumeRule = new CartVolumeRule();

        $cartRuleScope = $this->createMock(CartRuleScope::class);

        $this->expectException(UnsupportedValueException::class);
        $this->expectExceptionMessage('Unsupported value of type NULL in Cicada\Core\Checkout\Cart\Rule\CartVolumeRule');

        $cartVolumeRule->match($cartRuleScope);
    }

    #[DataProvider('matchTestDataProvider')]
    public function testMatch(string $operator, float $ruleVolume, bool $expectedResult): void
    {
        $cartVolumeRule = new CartVolumeRule($operator, $ruleVolume);

        $cart = Generator::createCartWithDelivery();
        $context = $this->createMock(SalesChannelContext::class);

        $cartRuleScope = new CartRuleScope($cart, $context);

        static::assertSame($expectedResult, $cartVolumeRule->match($cartRuleScope));
    }

    /**
     * More context: The ruleVolume is stored in cubic meters and the product volume is calculated in cubic millimeters.
     *
     * @return \Generator<array{operator:string, ruleVolume:float, expectedResult:bool}>
     */
    public static function matchTestDataProvider(): \Generator
    {
        yield 'Check with >= operator and 4000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_GTE,
            'ruleVolume' => 0.000004,
            'expectedResult' => true,
        ];

        yield 'Check with >= operator and 5000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_GTE,
            'ruleVolume' => 0.000005,
            'expectedResult' => true,
        ];

        yield 'Check with >= operator and 6000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_GTE,
            'ruleVolume' => 0.000006,
            'expectedResult' => false,
        ];

        yield 'Check with <= operator and 4000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_LTE,
            'ruleVolume' => 0.000004,
            'expectedResult' => false,
        ];

        yield 'Check with <= operator and 5000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_LTE,
            'ruleVolume' => 0.000005,
            'expectedResult' => true,
        ];

        yield 'Check with <= operator and 6000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_LTE,
            'ruleVolume' => 0.000006,
            'expectedResult' => true,
        ];

        yield 'Check with > operator and 4000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_GT,
            'ruleVolume' => 0.000004,
            'expectedResult' => true,
        ];

        yield 'Check with > operator and 5000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_GT,
            'ruleVolume' => 0.000005,
            'expectedResult' => false,
        ];

        yield 'Check with > operator and 6000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_GT,
            'ruleVolume' => 6000.0,
            'expectedResult' => false,
        ];

        yield 'Check with < operator and 4000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_LT,
            'ruleVolume' => 0.000004,
            'expectedResult' => false,
        ];

        yield 'Check with < operator and 5000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_LT,
            'ruleVolume' => 0.000005,
            'expectedResult' => false,
        ];

        yield 'Check with < operator and 6000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_LT,
            'ruleVolume' => 0.000006,
            'expectedResult' => true,
        ];

        yield 'Check with = operator and 4000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleVolume' => 0.000004,
            'expectedResult' => false,
        ];

        yield 'Check with = operator and 5000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleVolume' => 0.000005,
            'expectedResult' => true,
        ];

        yield 'Check with = operator and 6000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_EQ,
            'ruleVolume' => 0.000006,
            'expectedResult' => false,
        ];

        yield 'Check with != operator and 4000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleVolume' => 0.000004,
            'expectedResult' => true,
        ];

        yield 'Check with != operator and 5000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleVolume' => 0.000005,
            'expectedResult' => false,
        ];

        yield 'Check with != operator and 6000.0 mm^3 volume, should return true' => [
            'operator' => Rule::OPERATOR_NEQ,
            'ruleVolume' => 0.000006,
            'expectedResult' => true,
        ];

        yield 'Check with empty operator and 5000.0 mm^3 volume, should return false' => [
            'operator' => Rule::OPERATOR_EMPTY,
            'ruleVolume' => 0.000005,
            'expectedResult' => false,
        ];
    }

    public function testGetConstraints(): void
    {
        $cartVolumeRule = new CartVolumeRule();

        $result = $cartVolumeRule->getConstraints();

        static::assertArrayHasKey('volume', $result);
        static::assertArrayHasKey('operator', $result);

        static::assertIsArray($result['volume']);
        static::assertIsArray($result['operator']);
    }

    public function testGetConfig(): void
    {
        $cartVolumeRule = new CartVolumeRule();

        $result = $cartVolumeRule->getConfig()->getData();

        static::assertIsArray($result['operatorSet']['operators']);
        static::assertSame('volume', $result['fields']['volume']['config']['unit']);
    }
}
