<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Event\Subscriber;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Event\CartMergedEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Event\CartMergedSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class CartMergedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testMergedHintIsAdded(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(static::once())
            ->method('trans')
            ->with('checkout.cart-merged-hint')
            ->willReturn('checkout.cart-merged-hint');

        $subscriber = new CartMergedSubscriber($translator, $requestStack);

        $currentContextToken = 'currentToken';
        $currentContext = $this->createSalesChannelContext($currentContextToken, []);

        // Create Guest cart
        $previousCart = new Cart($currentContextToken);

        $productId1 = $this->createProduct($currentContext->getContext());
        $productId2 = $this->createProduct($currentContext->getContext());

        $productLineItem1 = new LineItem($productId1, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId1);
        $productLineItem2 = new LineItem($productId2, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId2);
        $productLineItem1->setStackable(true);
        $productLineItem2->setStackable(true);
        $productLineItem1->setQuantity(1);
        $productLineItem2->setQuantity(5);

        $previousCart->addLineItems(new LineItemCollection([$productLineItem1, $productLineItem2]));
        $previousCart->markUnmodified();

        $cartMergedEvent = new CartMergedEvent(new Cart('customerToken'), $currentContext, $previousCart);

        $subscriber->addCartMergedNoticeFlash($cartMergedEvent);

        static::assertNotEmpty($infoFlash = $session->getFlashBag()->get('info'));

        static::assertEquals('checkout.cart-merged-hint', $infoFlash[0]);
    }

    /**
     * @param array<string, mixed> $salesChannelData
     */
    private function createSalesChannelContext(string $contextToken, array $salesChannelData, ?string $customerId = null): SalesChannelContext
    {
        if ($customerId) {
            $salesChannelData[SalesChannelContextService::CUSTOMER_ID] = $customerId;
        }

        return static::getContainer()->get(SalesChannelContextFactory::class)->create(
            $contextToken,
            TestDefaults::SALES_CHANNEL,
            $salesChannelData
        );
    }

    private function createProduct(Context $context): string
    {
        $productId = Uuid::randomHex();

        $productNumber = Uuid::randomHex();
        $data = [
            'id' => $productId,
            'productNumber' => $productNumber,
            'stock' => 1,
            'name' => 'Test Product',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10.99, 'net' => 11.99, 'linked' => false]],
            'manufacturer' => ['name' => 'create'],
            'taxId' => $this->getValidTaxId(),
            'active' => true,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];
        static::getContainer()->get('product.repository')->create([$data], $context);

        return $productId;
    }
}
