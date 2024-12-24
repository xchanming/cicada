<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Promotion\Cart\Error\PromotionsOnCartPriceZeroError;
use Cicada\Core\Checkout\Promotion\Cart\PromotionCalculator;
use Cicada\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PromotionProcessor::class)]
class PromotionProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $promotionCalculatorMock = $this->createMock(PromotionCalculator::class);
        $groupBuilderMock = $this->createMock(LineItemGroupBuilder::class);

        $promotionProcessor = new PromotionProcessor($promotionCalculatorMock, $groupBuilderMock);

        $originalCart = new Cart('test');
        $originalCart->add(new LineItem('A', 'promotion', 'A', 2)); // 2 items of promotion A

        $toCalculateCart = new Cart('test');
        $toCalculateCart->setPrice(new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET));

        $context = $this->createMock(SalesChannelContext::class);
        $behavior = new CartBehavior();

        $data = new CartDataCollection();
        $data->set(PromotionProcessor::DATA_KEY, new LineItemCollection(
            [new LineItem('B', PromotionProcessor::LINE_ITEM_TYPE, Uuid::randomHex(), 1)],
        ));

        $promotionCalculatorMock->expects(static::once())
            ->method('calculate')
            ->with(
                static::callback(function (LineItemCollection $data) {
                    static::assertTrue($data->has('B'));
                    static::assertTrue($data->get('B')->isShippingCostAware());

                    return true;
                }),
                static::anything(),
                static::anything(),
                static::anything()
            );

        $promotionProcessor->process($data, $originalCart, $toCalculateCart, $context, $behavior);
    }

    public function testProcessWithCartZeroPriceAndPromotionIsGlobal(): void
    {
        $promotionCalculatorMock = $this->createMock(PromotionCalculator::class);
        $groupBuilderMock = $this->createMock(LineItemGroupBuilder::class);

        $promotionProcessor = new PromotionProcessor($promotionCalculatorMock, $groupBuilderMock);

        $originalCart = new Cart('test');
        $originalCart->add(new LineItem('A', 'promotion', 'A', 2)); // 2 items of promotion A

        $toCalculateCart = new Cart('test');
        $toCalculateCart->setPrice(new CartPrice(0, 0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET));

        $context = $this->createMock(SalesChannelContext::class);
        $behavior = new CartBehavior();

        $data = new CartDataCollection();
        $data->set(PromotionProcessor::DATA_KEY, new LineItemCollection(
            // `promotionCodeType` => global means the promotion is automatically applied if matched conditions
            [(new LineItem('B', PromotionProcessor::LINE_ITEM_TYPE, Uuid::randomHex(), 1))->setPayload(['promotionCodeType' => PromotionItemBuilder::PROMOTION_TYPE_GLOBAL])],
        ));

        $promotionCalculatorMock->expects(static::never())
            ->method('calculate');

        $promotionProcessor->process($data, $originalCart, $toCalculateCart, $context, $behavior);

        static::assertEquals(0, $toCalculateCart->getErrors()->count());
    }

    public function testProcessWithCartZeroPriceAndPromotionIsNotGlobal(): void
    {
        $promotionCalculatorMock = $this->createMock(PromotionCalculator::class);
        $groupBuilderMock = $this->createMock(LineItemGroupBuilder::class);

        $promotionProcessor = new PromotionProcessor($promotionCalculatorMock, $groupBuilderMock);

        $originalCart = new Cart('test');
        $originalCart->add(new LineItem('A', 'promotion', 'A', 2)); // 2 items of promotion A

        $toCalculateCart = new Cart('test');
        $toCalculateCart->setPrice(new CartPrice(0, 0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET));

        $context = $this->createMock(SalesChannelContext::class);
        $behavior = new CartBehavior();

        $data = new CartDataCollection();
        $data->set(PromotionProcessor::DATA_KEY, new LineItemCollection(
            // `promotionCodeType` => fixed means the promotion is applied only if the promotion code is input.
            [(new LineItem('B', PromotionProcessor::LINE_ITEM_TYPE, Uuid::randomHex(), 1))->setPayload(['promotionCodeType' => PromotionItemBuilder::PROMOTION_TYPE_FIXED])],
        ));

        $promotionCalculatorMock->expects(static::never())
            ->method('calculate');

        $promotionProcessor->process($data, $originalCart, $toCalculateCart, $context, $behavior);

        static::assertEquals(1, $toCalculateCart->getErrors()->count());
        static::assertInstanceOf(PromotionsOnCartPriceZeroError::class, $toCalculateCart->getErrors()->first());
    }
}
