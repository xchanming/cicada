<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CheckoutRuleScope::class)]
class CheckoutRuleScopeTest extends TestCase
{
    public function testConstruct(): void
    {
        $scope = new CheckoutRuleScope($context = Generator::generateSalesChannelContext());

        static::assertSame($context, $scope->getSalesChannelContext());
        static::assertSame($context->getContext(), $scope->getContext());
    }
}
