<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Validator\Container;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Rule\Container\XorRule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Core\Test\Stub\Rule\TrueRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(XorRule::class)]
class XorRuleTest extends TestCase
{
    public function testSingleTrueRule(): void
    {
        $rule = new XorRule([
            new FalseRule(),
            new TrueRule(),
            new FalseRule(),
        ]);

        static::assertTrue(
            $rule->match(
                new CheckoutRuleScope(
                    $this->createMock(SalesChannelContext::class)
                )
            )
        );
    }

    public function testWithMultipleFalse(): void
    {
        $rule = new XorRule([
            new FalseRule(),
            new FalseRule(),
        ]);

        static::assertFalse(
            $rule->match(
                new CheckoutRuleScope(
                    $this->createMock(SalesChannelContext::class)
                )
            )
        );
    }

    public function testWithMultipleTrue(): void
    {
        $rule = new XorRule([
            new TrueRule(),
            new TrueRule(),
            new FalseRule(),
        ]);

        static::assertFalse(
            $rule->match(
                new CheckoutRuleScope(
                    $this->createMock(SalesChannelContext::class)
                )
            )
        );
    }
}
