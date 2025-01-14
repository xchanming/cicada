<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout;

use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Integration\Helper\MailEventListener;
use Cicada\Core\Test\Integration\Traits\TestShortHands;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BasicOrderProductTest extends TestCase
{
    use IntegrationTestBehaviour;
    use TestShortHands;

    public function testBasicOrder(): void
    {
        $product = (new ProductBuilder(new IdsCollection(), 'p1'))
            ->stock(100)
            ->price(100)
            ->visibility();

        // the product builder has a helper function to write the product values to the database, including all dependencies (rules, currencies, properties, etc)
        $product->write(static::getContainer());

        $context = $this->getContext();
        $context = $this->login($context);

        // now we test that the product can be added to a customers cart
        $cart = $this->addProductToCart($product->id, $context);

        $this->assertLineItemInCart($cart, $product->id);

        $this->assertLineItemUnitPrice($cart, $product->id, 100);

        $this->assertLineItemTotalPrice($cart, $product->id, 100);

        $orderId = $this->mailListener(function (MailEventListener $listener) use ($cart, $context) {
            return $this->order($cart, $context);
        });

        $item = $this->assertProductInOrder($orderId, $product->id);

        static::assertEquals(100, $item->getUnitPrice());

        static::assertEquals(100, $item->getTotalPrice());

        $this->assertStock($product->id, 99, 99);
    }
}
