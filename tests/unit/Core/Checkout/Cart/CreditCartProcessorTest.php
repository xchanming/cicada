<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CreditCartProcessor;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CreditCartProcessor::class)]
class CreditCartProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $data = new CartDataCollection();
        $item = new LineItem('hatoken', 'credit', 'a', 2);
        $item->setPriceDefinition(new AbsolutePriceDefinition(5.0));

        $original = new Cart('original');
        $original->add($item);

        $toCalculate = new Cart('toCalculate');
        $context = Generator::createSalesChannelContext();
        $behavior = new CartBehavior($context->getPermissions());

        $calculator = $this->createMock(AbsolutePriceCalculator::class);
        $calculator
            ->expects(static::once())
            ->method('calculate')
            ->with(
                static::equalTo(5.0),
                static::equalTo($toCalculate->getLineItems()->getPrices()),
                static::equalTo($context)
            )
            ->willReturn(new CalculatedPrice(5.0, 10.0, new CalculatedTaxCollection(), new TaxRuleCollection()));

        $processor = new CreditCartProcessor($calculator);
        $processor->process($data, $original, $toCalculate, $context, $behavior);

        static::assertCount(1, $toCalculate->getLineItems());
        static::assertEquals(10.0, $toCalculate->getLineItems()->first()?->getPrice()?->getTotalPrice());
    }

    public function testNoneCreditItemsIgnored(): void
    {
        $data = new CartDataCollection();
        $item = new LineItem('hatoken', 'product', 'a', 2);
        $item->setPriceDefinition(new AbsolutePriceDefinition(5.0));

        $original = new Cart('original');
        $original->add($item);

        $toCalculate = new Cart('toCalculate');
        $context = Generator::createSalesChannelContext();
        $behavior = new CartBehavior($context->getPermissions());

        $calculator = $this->createMock(AbsolutePriceCalculator::class);
        $calculator
            ->expects(static::never())
            ->method('calculate');

        $processor = new CreditCartProcessor($calculator);
        $processor->process($data, $original, $toCalculate, $context, $behavior);

        static::assertCount(0, $toCalculate->getLineItems());
    }

    public function testNonAbsolutePricesIgnored(): void
    {
        $data = new CartDataCollection();
        $item = new LineItem('hatoken', 'product', 'a', 2);
        $item->setPriceDefinition(new QuantityPriceDefinition(5.0, new TaxRuleCollection(), 2));

        $original = new Cart('original');
        $original->add($item);

        $toCalculate = new Cart('toCalculate');
        $context = Generator::createSalesChannelContext();
        $behavior = new CartBehavior($context->getPermissions());

        $calculator = $this->createMock(AbsolutePriceCalculator::class);
        $calculator
            ->expects(static::never())
            ->method('calculate');

        $processor = new CreditCartProcessor($calculator);
        $processor->process($data, $original, $toCalculate, $context, $behavior);

        static::assertCount(0, $toCalculate->getLineItems());
    }
}
