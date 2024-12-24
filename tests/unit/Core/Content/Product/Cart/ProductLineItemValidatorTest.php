<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\LineItem\QuantityInformation;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Content\Product\Cart\ProductCartProcessor;
use Cicada\Core\Content\Product\Cart\ProductLineItemValidator;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(ProductLineItemValidator::class)]
class ProductLineItemValidatorTest extends TestCase
{
    public function testSkipStockValidation(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $builder = new ProductLineItemFactory(new PriceDefinitionFactory());
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $cart->add(
            $builder
                ->create(['id' => 'product-1', 'referencedId' => 'product-1'], $salesChannelContext)
                ->setQuantityInformation(
                    (new QuantityInformation())
                        ->setMinPurchase(1)
                        ->setMaxPurchase(1)
                        ->setPurchaseSteps(1)
                )
        );
        $cart->add(
            $builder
                ->create(['id' => 'product-2', 'referencedId' => 'product-2'], $salesChannelContext)
                ->setReferencedId('product-1')
                ->setQuantityInformation(
                    (new QuantityInformation())
                        ->setMinPurchase(1)
                        ->setMaxPurchase(1)
                        ->setPurchaseSteps(1)
                )
        );

        $cart->setBehavior(new CartBehavior([
            ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION => true,
        ]));

        static::assertCount(0, $cart->getErrors());

        $validator = new ProductLineItemValidator();
        $validator->validate($cart, $cart->getErrors(), $salesChannelContext);

        static::assertCount(0, $cart->getErrors());
    }

    public function testValidateOnDuplicateProductsAtMaxPurchase(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $builder = new ProductLineItemFactory(new PriceDefinitionFactory());
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $cart->add(
            $builder
            ->create(['id' => 'product-1', 'referencedId' => 'product-1'], $salesChannelContext)
            ->setQuantityInformation(
                (new QuantityInformation())
                ->setMinPurchase(1)
                ->setMaxPurchase(1)
                ->setPurchaseSteps(1)
            )
        );
        $cart->add(
            $builder
            ->create(['id' => 'product-2', 'referencedId' => 'product-2'], $salesChannelContext)
            ->setReferencedId('product-1')
            ->setQuantityInformation(
                (new QuantityInformation())
                ->setMinPurchase(1)
                ->setMaxPurchase(1)
                ->setPurchaseSteps(1)
            )
        );

        static::assertCount(0, $cart->getErrors());

        $validator = new ProductLineItemValidator();
        $validator->validate($cart, $cart->getErrors(), $salesChannelContext);

        static::assertCount(1, $cart->getErrors());
    }

    public function testValidateOnDuplicateProductsWithSafeQuantity(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $builder = new ProductLineItemFactory(new PriceDefinitionFactory());
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $cart->add(
            $builder
            ->create(['id' => 'product-1', 'referencedId' => 'product-1'], $salesChannelContext)
            ->setQuantityInformation(
                (new QuantityInformation())
                ->setMinPurchase(1)
                ->setMaxPurchase(3)
                ->setPurchaseSteps(1)
            )
        );
        $cart->add(
            $builder
            ->create(['id' => 'product-2', 'referencedId' => 'product-2'], $salesChannelContext)
            ->setReferencedId('product-1')
            ->setQuantityInformation(
                (new QuantityInformation())
                ->setMinPurchase(1)
                ->setMaxPurchase(3)
                ->setPurchaseSteps(1)
            )
        );

        static::assertCount(0, $cart->getErrors());

        $validator = new ProductLineItemValidator();
        $validator->validate($cart, $cart->getErrors(), $salesChannelContext);

        static::assertCount(0, $cart->getErrors());
    }

    public function testValidateOnDuplicateProductsWithoutQuantityInformation(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $builder = new ProductLineItemFactory(new PriceDefinitionFactory());
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $cart->add($builder->create(['id' => 'product-1', 'referencedId' => 'product-1'], $salesChannelContext));
        $cart->add($builder->create(['id' => 'product-2', 'referencedId' => 'product-2'], $salesChannelContext)->setReferencedId('product-1'));

        static::assertCount(0, $cart->getErrors());

        $validator = new ProductLineItemValidator();
        $validator->validate($cart, $cart->getErrors(), $salesChannelContext);

        static::assertCount(0, $cart->getErrors());
    }
}
