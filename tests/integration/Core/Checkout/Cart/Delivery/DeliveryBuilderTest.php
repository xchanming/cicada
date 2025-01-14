<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Delivery;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\Delivery\DeliveryBuilder;
use Cicada\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\DeliveryTime\DeliveryTimeEntity;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class DeliveryBuilderTest extends TestCase
{
    use IntegrationTestBehaviour;

    private DeliveryBuilder $builder;

    private SalesChannelContext $context;

    private DeliveryProcessor $processor;

    protected function setUp(): void
    {
        $this->builder = static::getContainer()->get(DeliveryBuilder::class);

        $this->processor = static::getContainer()->get(DeliveryProcessor::class);

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    public function testIndependenceOfLineItemAndDeliveryPositionPrices(): void
    {
        $cart = $this->createCart();
        $firstDelivery = $this->getDeliveries($cart)->first();
        static::assertNotNull($firstDelivery);

        $firstPosition = $firstDelivery->getPositions()->first();

        static::assertNotSame($firstPosition?->getPrice(), $cart->getLineItems()->first()?->getPrice());
    }

    public function testEmptyCart(): void
    {
        $cart = $this->createCart(true);
        $deliveries = $this->getDeliveries($cart);

        static::assertCount(0, $deliveries);
    }

    public function testBuildDeliveryWithEqualMinAndMaxDeliveryDateThatLatestHasOneDayMore(): void
    {
        $cart = $this->createCart();
        $firstDelivery = $this->getDeliveries($cart)->first();
        static::assertNotNull($firstDelivery);

        $deliveryDate = $firstDelivery->getDeliveryDate();
        $earliestDeliveryDate = $deliveryDate->getEarliest();
        $earliestDeliveryDate = $earliestDeliveryDate->add(new \DateInterval('P1D'));
        $latestDeliveryDate = $deliveryDate->getLatest();

        static::assertSame(
            $latestDeliveryDate->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            $earliestDeliveryDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        );
    }

    private function getDeliveries(Cart $cart): DeliveryCollection
    {
        $data = new CartDataCollection();
        $cartBehaviour = new CartBehavior();

        $this->processor->collect($data, $cart, $this->context, $cartBehaviour);

        return $this->builder->build($cart, $data, $this->context, $cartBehaviour);
    }

    private function createCart(bool $withoutLineItems = false): Cart
    {
        $cart = new Cart('test');
        if ($withoutLineItems) {
            return $cart;
        }

        $lineItems = $this->createLineItems();
        $cart->addLineItems($lineItems);

        return $cart;
    }

    private function createLineItems(): LineItemCollection
    {
        $lineItems = new LineItemCollection();
        $lineItem = $this->createLineItem();
        $lineItems->add($lineItem);

        return $lineItems;
    }

    private function createLineItem(): LineItem
    {
        $lineItem = new LineItem('testid', LineItem::PRODUCT_LINE_ITEM_TYPE);

        $deliveryInformation = $this->createDeliveryInformation();
        $lineItem->setDeliveryInformation($deliveryInformation);

        $price = new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection());
        $lineItem->setPrice($price);
        $lineItem->setShippingCostAware(true);

        return $lineItem;
    }

    private function createDeliveryInformation(): DeliveryInformation
    {
        $deliveryTime = $this->createDeliveryTime();

        return new DeliveryInformation(100, 10.0, false, null, $deliveryTime);
    }

    private function createDeliveryTime(): DeliveryTime
    {
        $deliveryTime = new DeliveryTime();
        $deliveryTime->setMin(2);
        $deliveryTime->setMax(2);
        $deliveryTime->setUnit(DeliveryTimeEntity::DELIVERY_TIME_MONTH);

        return $deliveryTime;
    }
}
