<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Event\WishlistProductAddedEvent;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
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
class AddWishlistProductRouteTest extends TestCase
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

        $email = Uuid::randomHex() . '@example.com';
        $this->customerId = $this->createCustomer($email);

        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $this->systemConfigService->set('core.cart.wishlistEnabled', true);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => 'cicada',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);
    }

    public function testAddProductShouldReturnSuccess(): void
    {
        $productData = $this->createProduct($this->context);
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $eventWasThrown = false;

        $listener = static function (WishlistProductAddedEvent $event) use ($productData, &$eventWasThrown): void {
            static::assertSame($productData[0], $event->getProductId());
            $eventWasThrown = true;
        };
        $dispatcher->addListener(WishlistProductAddedEvent::class, $listener);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist/add/' . $productData[0]
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertTrue($response['success']);
        static::assertTrue($eventWasThrown);

        $dispatcher->removeListener(WishlistProductAddedEvent::class, $listener);
    }

    public function testAddProductShouldThrowCustomerWishlistNotActivatedException(): void
    {
        $productData = $this->createProduct($this->context);
        $this->systemConfigService->set('core.cart.wishlistEnabled', false);

        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist/add/' . $productData[0]
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(403, $this->browser->getResponse()->getStatusCode());
        static::assertEquals('CHECKOUT__WISHLIST_IS_NOT_ACTIVATED', $errors['code']);
        static::assertEquals('Forbidden', $errors['title']);
        static::assertEquals('Wishlist is not activated!', $errors['detail']);
    }

    public function testAddProductShouldThrowCustomerNotLoggedInException(): void
    {
        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', Random::getAlphanumericString(12));

        $productId = Uuid::randomHex();
        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist/add/' . $productId
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(403, $this->browser->getResponse()->getStatusCode());
        if (Feature::isActive('v6.7.0.0')) {
            static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
        } else {
            static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
        }
        static::assertEquals('Forbidden', $errors['title']);
        static::assertEquals('Customer is not logged in.', $errors['detail']);
    }

    public function testAddProductShouldThrowDuplicateWishlistProductException(): void
    {
        $productData = $this->createProduct($this->context);
        $this->createCustomerWishlist($this->context, $this->customerId, $productData[0]);
        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist/add/' . $productData[0]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $errors = $response['errors'][0];
        unset($errors['meta']);

        static::assertSame(400, $this->browser->getResponse()->getStatusCode(), print_r($errors, true));
        static::assertEquals('CHECKOUT__DUPLICATE_WISHLIST_PRODUCT', $errors['code']);
    }

    public function testAddProductShouldThrowProductNotFoundException(): void
    {
        $productId = Uuid::randomHex();
        $this->browser
            ->request(
                'POST',
                '/store-api/customer/wishlist/add/' . $productId
            );
        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $errors = $response['errors'][0];
        static::assertSame(404, $this->browser->getResponse()->getStatusCode());
        static::assertEquals('CONTENT__PRODUCT_NOT_FOUND', $errors['code']);
        static::assertEquals('Not Found', $errors['title']);
        static::assertEquals('Product for id ' . $productId . ' not found.', $errors['detail']);
    }

    /**
     * @return array<int, string>
     */
    private function createProduct(Context $context): array
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
                ['salesChannelId' => $this->getSalesChannelApiSalesChannelId(), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        static::getContainer()->get('product.repository')->create([$data], $context);

        return [$productId, $productNumber];
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
