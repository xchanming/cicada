<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\NotRule;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\SimpleRule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(NotRule::class)]
class NotRuleTest extends TestCase
{
    public function testUnsupportedValue(): void
    {
        $this->expectException(UnsupportedValueException::class);
        $rule = new NotRule();
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $rule->match(new CheckoutRuleScope($salesChannelContext));
    }

    public function testAddRuleOnlyAllowsOneRule(): void
    {
        $this->expectException(\RuntimeException::class);

        $rule = new NotRule();
        $rule->addRule(new SimpleRule());
        $rule->addRule(new SimpleRule());
    }

    public function testSetRulesOnlyAllowsOneRule(): void
    {
        $this->expectException(\RuntimeException::class);

        $rule = new NotRule();
        $rule->setRules([new SimpleRule(), new SimpleRule()]);
    }
}
