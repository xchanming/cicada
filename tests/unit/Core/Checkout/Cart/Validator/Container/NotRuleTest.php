<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Validator\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Rule\Container\NotRule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Core\Test\Stub\Rule\TrueRule;

/**
 * @internal
 */
#[CoversClass(NotRule::class)]
class NotRuleTest extends TestCase
{
    public function testTrue(): void
    {
        $rule = new NotRule([
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

    public function testExceptionByMultipleRules(): void
    {
        $this->expectException(\RuntimeException::class);
        new NotRule([
            new FalseRule(),
            new FalseRule(),
            new FalseRule(),
        ]);
    }

    public function testFalse(): void
    {
        $rule = new NotRule([
            new TrueRule(),
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
