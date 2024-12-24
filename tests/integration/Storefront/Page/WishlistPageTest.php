<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Page\PageLoadedEvent;
use Cicada\Storefront\Page\Wishlist\WishlistPageLoadedEvent;
use Cicada\Storefront\Page\Wishlist\WishlistPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class WishlistPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
    }

    public function testInActiveWishlist(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->systemConfigService->set('core.cart.wishlistEnabled', false);

        $this->expectException(CustomerException::class);

        $customer = $context->getCustomer();
        static::assertInstanceOf(CustomerEntity::class, $customer);

        $this->getPageLoader()->load($request, $context, $customer);
    }

    public function testWishlistNotFound(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->systemConfigService->set('core.cart.wishlistEnabled', true);

        $page = $this->getPageLoader()->load($request, $context, $this->createCustomer());

        static::assertSame(0, $page->getWishlist()->getProductListing()->getTotal());
    }

    public function testItLoadsWishlistPage(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->systemConfigService->set('core.cart.wishlistEnabled', true);

        $product = $this->getRandomProduct($context);
        $customer = $context->getCustomer();
        static::assertInstanceOf(CustomerEntity::class, $customer);
        $this->createCustomerWishlist($customer->getId(), $product->getId(), $context->getSalesChannel()->getId());

        $event = null;
        $this->catchEvent(WishlistPageLoadedEvent::class, $event);
        $page = $this->getPageLoader()->load($request, $context, $customer);

        static::assertInstanceOf(PageLoadedEvent::class, $event);

        static::assertSame(1, $page->getWishlist()->getProductListing()->getTotal());
        static::assertSame($context->getContext(), $page->getWishlist()->getProductListing()->getContext());
        self::assertPageEvent(WishlistPageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): WishlistPageLoader
    {
        return static::getContainer()->get(WishlistPageLoader::class);
    }

    private function createCustomerWishlist(string $customerId, string $productId, string $salesChannelId): string
    {
        $customerWishlistId = Uuid::randomHex();
        $customerWishlistRepository = static::getContainer()->get('customer_wishlist.repository');

        $customerWishlistRepository->create([
            [
                'id' => $customerWishlistId,
                'customerId' => $customerId,
                'salesChannelId' => $salesChannelId,
                'products' => [
                    [
                        'productId' => $productId,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        return $customerWishlistId;
    }
}
