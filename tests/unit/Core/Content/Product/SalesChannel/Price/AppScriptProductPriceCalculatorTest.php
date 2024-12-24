<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Price;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Content\Product\SalesChannel\Price\AppScriptProductPriceCalculator;
use Cicada\Core\Content\Product\SalesChannel\Price\ProductPriceCalculator;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(AppScriptProductPriceCalculator::class)]
class AppScriptProductPriceCalculatorTest extends TestCase
{
    public function testHookWillBeExecuted(): void
    {
        $products = [
            new SalesChannelProductEntity(),
            new SalesChannelProductEntity(),
        ];

        $executor = $this->createMock(ScriptExecutor::class);
        $executor->expects(static::once())->method('execute');

        $decorated = $this->createMock(ProductPriceCalculator::class);
        $decorated->expects(static::once())->method('calculate')->with($products);

        $calculator = new AppScriptProductPriceCalculator($decorated, $executor, $this->createMock(ScriptPriceStubs::class));

        $calculator->calculate($products, $this->createMock(SalesChannelContext::class));
    }
}
