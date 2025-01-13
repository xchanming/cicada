<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Rule\SalesChannelRule;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(SalesChannelRule::class)]
class SalesChannelRuleTest extends TestCase
{
    /**
     * @param list<string> $salesChannelIds
     */
    #[DataProvider('provideTestData')]
    public function testMatchesWithCorrectSalesChannel(string $operator, string $currentSalesChannel, ?array $salesChannelIds, bool $expected): void
    {
        $ruleScope = $this->createRuleScope($currentSalesChannel);

        $salesChannelRule = new SalesChannelRule($operator, $salesChannelIds);

        static::assertSame($expected, $salesChannelRule->match($ruleScope));
    }

    public static function provideTestData(): \Generator
    {
        yield 'matches with correct sales channel' => [
            Rule::OPERATOR_EQ,
            Uuid::fromStringToHex('test'),
            [Uuid::fromStringToHex('test')],
            true,
        ];

        yield 'matches with wrong sales channel' => [
            Rule::OPERATOR_EQ,
            Uuid::fromStringToHex('test'),
            [Uuid::fromStringToHex('test1')],
            false,
        ];

        yield 'matches with multiple sales channel' => [
            Rule::OPERATOR_EQ,
            Uuid::fromStringToHex('test'),
            [Uuid::fromStringToHex('test1'), Uuid::fromStringToHex('test'), Uuid::fromStringToHex('test2')],
            true,
        ];

        yield 'matches not equal with valid sales channel' => [
            Rule::OPERATOR_NEQ,
            Uuid::fromStringToHex('test'),
            [Uuid::fromStringToHex('test1')],
            true,
        ];

        yield 'matches not equal with invalid sales channel' => [
            Rule::OPERATOR_NEQ,
            Uuid::fromStringToHex('test'),
            [Uuid::fromStringToHex('test')],
            false,
        ];

        yield 'matches with empty sales channel ids' => [
            Rule::OPERATOR_EQ,
            Uuid::fromStringToHex('test'),
            [],
            false,
        ];

        yield 'matches with null channel ids' => [
            Rule::OPERATOR_EQ,
            Uuid::fromStringToHex('test'),
            null,
            false,
        ];
    }

    public function testProvidesConstraints(): void
    {
        $salesChannelRule = new SalesChannelRule(Rule::OPERATOR_EQ, []);
        $constraints = $salesChannelRule->getConstraints();

        static::assertArrayHasKey('salesChannelIds', $constraints);
        static::assertEquals(RuleConstraints::uuids(), $constraints['salesChannelIds']);

        static::assertArrayHasKey('operator', $constraints);
        static::assertEquals(RuleConstraints::uuidOperators(false), $constraints['operator']);
    }

    private function createRuleScope(string $salesChannelId): RuleScope
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);

        $salesChannelContext = Generator::generateSalesChannelContext(
            salesChannel: $salesChannel
        );

        return new CheckoutRuleScope(
            $salesChannelContext
        );
    }
}
