<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Group('store-api')]
class LoadWishlistRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private Context $context;

    private string $customerId;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);

        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $this->systemConfigService->set('core.cart.wishlistEnabled', true);

        $email = Uuid::randomHex() . '@example.com';
        $this->customerId = $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => '12345678',
                ]
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);
    }

    public function testLoadShouldReturnSuccess(): void
    {
        $productId = $this->createProduct($this->context);
        $customerWishlistId = $this->createCustomerWishlist($this->context, $this->customerId, $productId);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $wishlist = $response['wishlist'];
        $products = $response['products'];

        static::assertNotEmpty($response);
        static::assertEquals($customerWishlistId, $wishlist['id']);
        static::assertEquals(1, $products['total']);
        static::assertNotNull($products['elements']);
    }

    public function testDeleteProductShouldThrowCustomerWishlistNotActivatedException(): void
    {
        $this->systemConfigService->set('core.cart.wishlistEnabled', false);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(403, $this->browser->getResponse()->getStatusCode());
        static::assertEquals('CHECKOUT__WISHLIST_IS_NOT_ACTIVATED', $errors['code']);
        static::assertEquals('Forbidden', $errors['title']);
        static::assertEquals('Wishlist is not activated!', $errors['detail']);
    }

    public function testLoadShouldThrowCustomerNotLoggedInException(): void
    {
        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', Random::getAlphanumericString(12));

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(403, $this->browser->getResponse()->getStatusCode());
        static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $errors['code']);
        static::assertSame('Forbidden', $errors['title']);
        static::assertSame('Customer is not logged in.', $errors['detail']);
    }

    public function testLoadShouldThrowCustomerWishlistNotFoundException(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(404, $this->browser->getResponse()->getStatusCode());
        static::assertEquals('CHECKOUT__WISHLIST_NOT_FOUND', $errors['code']);
        static::assertEquals('Not Found', $errors['title']);
        static::assertEquals('Wishlist for this customer was not found.', $errors['detail']);
    }

    public function testLoadWithHideCloseoutProductsWhenOutOfStockEnabled(): void
    {
        // enable hideCloseoutProductsWhenOutOfStock filter
        static::getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', true);

        $productId = $this->createProduct($this->context, ['stock' => 0, 'isCloseout' => true]);
        $this->createCustomerWishlist($this->context, $this->customerId, $productId);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $products = $response['products'];

        static::assertEquals(0, $products['total']);
    }

    public function testLoadWithHideCloseoutProductsWhenOutOfStockDisabled(): void
    {
        // disabled hideCloseoutProductsWhenOutOfStock filter
        static::getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', false);

        $productId = $this->createProduct($this->context, ['stock' => 0, 'isCloseout' => true]);
        $this->createCustomerWishlist($this->context, $this->customerId, $productId);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist'
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $products = $response['products'];

        static::assertEquals(1, $products['total']);
        static::assertNotNull($products['elements']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createProduct(Context $context, array $attributes = []): string
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
                [
                    'salesChannelId' => $this->getSalesChannelApiSalesChannelId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        static::getContainer()->get('product.repository')->create([array_merge($data, $attributes)], $context);

        return $productId;
    }

    private function createCustomerWishlist(Context $context, string $customerId, string $productId): string
    {
        $customerWishlistId = Uuid::randomHex();
        $customerWishlistRepository = static::getContainer()->get('customer_wishlist.repository');

        $customerWishlistRepository->create([
            [
                'id' => $customerWishlistId,
                'customerId' => $customerId,
                'salesChannelId' => $this->getSalesChannelApiSalesChannelId(),
                'products' => [
                    [
                        'productId' => $productId,
                    ],
                ],
            ],
        ], $context);

        return $customerWishlistId;
    }
}
