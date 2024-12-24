<?php declare(strict_types=1);

namespace Cicada\Storefront\Test\Page;

use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Page\PageLoadedEvent;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Cicada\Tests\Integration\Storefront\Page\StorefrontPageTestConstants;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal - Will be internal in v6.7.0
 */
trait StorefrontPageTestBehaviour
{
    use TaxAddToSalesChannelTestBehaviour;

    /**
     * @template TEvent of PageLoadedEvent
     *
     * @param class-string<TEvent> $expectedClass
     * @param TEvent|null $event
     */
    public static function assertPageEvent(
        string $expectedClass,
        ?PageLoadedEvent $event,
        SalesChannelContext $salesChannelContext,
        Request $request,
        Struct $page
    ): void {
        TestCase::assertInstanceOf($expectedClass, $event);
        TestCase::assertSame($salesChannelContext, $event->getSalesChannelContext());
        TestCase::assertSame($salesChannelContext->getContext(), $event->getContext());
        TestCase::assertSame($request, $event->getRequest());
        TestCase::assertSame($page, $event->getPage());
    }

    /**
     * @template TEvent of PageletLoadedEvent
     *
     * @param class-string<TEvent> $expectedClass
     * @param TEvent $event
     */
    public static function assertPageletEvent(
        string $expectedClass,
        PageletLoadedEvent $event,
        SalesChannelContext $salesChannelContext,
        Request $request,
        Struct $page
    ): void {
        TestCase::assertInstanceOf($expectedClass, $event);
        TestCase::assertSame($salesChannelContext, $event->getSalesChannelContext());
        TestCase::assertSame($salesChannelContext->getContext(), $event->getContext());
        TestCase::assertSame($request, $event->getRequest());
        TestCase::assertSame($page, $event->getPagelet());
    }

    abstract protected function getPageLoader();

    protected function expectParamMissingException(string $paramName): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "' . $paramName . '" is missing');
    }

    protected function placeRandomOrder(SalesChannelContext $context): string
    {
        $product = $this->getRandomProduct($context);

        $lineItem = (new LineItem($product->getId(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId()))
            ->setRemovable(true)
            ->setStackable(true);

        $cartService = static::getContainer()->get(CartService::class);
        $cart = $cartService->getCart($context->getToken(), $context);
        $cart->add($lineItem);

        return $cartService->order($cart, $context, new RequestDataBag());
    }

    /**
     * @param array<int|string, mixed> $config
     */
    protected function getRandomProduct(SalesChannelContext $context, ?int $stock = 1, ?bool $isCloseout = false, array $config = []): ProductEntity
    {
        $id = Uuid::randomHex();
        $productNumber = Uuid::randomHex();
        $productRepository = static::getContainer()->get('product.repository');

        $data = [
            'id' => $id,
            'productNumber' => $productNumber,
            'stock' => $stock,
            'name' => StorefrontPageTestConstants::PRODUCT_NAME,
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['id' => Uuid::randomHex(), 'name' => 'test', 'taxRate' => 15],
            'active' => true,
            'isCloseout' => $isCloseout,
            'categories' => [
                ['id' => Uuid::randomHex(), 'name' => 'asd'],
            ],
            'visibilities' => [
                ['salesChannelId' => $context->getSalesChannel()->getId(), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $data = array_merge_recursive($data, $config);

        $productRepository->create([$data], $context->getContext());
        $this->addTaxDataToSalesChannel($context, $data['tax']);

        /** @var SalesChannelRepository<ProductCollection> $storefrontProductRepository */
        $storefrontProductRepository = static::getContainer()->get('sales_channel.product.repository');
        $product = $storefrontProductRepository->search(new Criteria([$id]), $context)->getEntities()->first();
        static::assertNotNull($product);

        return $product;
    }

    protected function createSalesChannelContextWithNavigation(): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        return $this->createContext($data, []);
    }

    protected function createSalesChannelContextWithLoggedInCustomerAndWithNavigation(): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        return $this->createContext($data, [
            SalesChannelContextService::CUSTOMER_ID => $this->createCustomer()->getId(),
        ]);
    }

    /**
     * @param array<string, mixed>|null $salesChannelData
     */
    protected function createSalesChannelContext(?array $salesChannelData = null): SalesChannelContext
    {
        $paymentMethodId = $this->getValidPaymentMethodId();
        $shippingMethodId = $this->getAvailableShippingMethod()->getId();
        $countryId = $this->getValidCountryId();
        $snippetSetId = $this->getSnippetSetIdForLocale('en-GB');
        $data = [
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'store front',
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'snippetSetId' => $snippetSetId,
            'currencyId' => Defaults::CURRENCY,
            'currencyVersionId' => Defaults::LIVE_VERSION,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethodVersionId' => Defaults::LIVE_VERSION,
            'shippingMethodId' => $shippingMethodId,
            'shippingMethodVersionId' => Defaults::LIVE_VERSION,
            'navigationCategoryId' => $this->getValidCategoryId(),
            'navigationCategoryVersionId' => Defaults::LIVE_VERSION,
            'countryId' => $countryId,
            'countryVersionId' => Defaults::LIVE_VERSION,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM]],
            'paymentMethods' => [['id' => $paymentMethodId]],
            'shippingMethods' => [['id' => $shippingMethodId]],
            'countries' => [['id' => $countryId]],
            'domains' => [
                ['url' => 'http://test.com/' . Uuid::randomHex(), 'currencyId' => Defaults::CURRENCY, 'languageId' => Defaults::LANGUAGE_SYSTEM, 'snippetSetId' => $snippetSetId],
            ],
        ];

        if ($salesChannelData) {
            $data = array_merge($data, $salesChannelData);
        }

        return $this->createContext($data, []);
    }

    /**
     * @template TEventName of Event
     *
     * @param class-string<TEventName> $eventName
     * @param TEventName|null $eventResult
     */
    protected function catchEvent(string $eventName, ?Event &$eventResult): void
    {
        $this->addEventListener(static::getContainer()->get('event_dispatcher'), $eventName, static function (Event $event) use (&$eventResult): void {
            $eventResult = $event;
        });
    }

    abstract protected static function getContainer(): ContainerInterface;

    private function createCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'street' => 'Musterstraße 1',
                'city' => 'Schöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'country' => ['id' => $this->getValidCountryId()],
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => 'foo@bar.de',
            'password' => 'password',
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        /** @var EntityRepository<CustomerCollection> $repo */
        $repo = static::getContainer()->get('customer.repository');

        $repo->create([$customer], Context::createDefaultContext());

        $customer = $repo->search(new Criteria([$customerId]), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($customer);

        return $customer;
    }

    /**
     * @param array<string, mixed> $salesChannel
     * @param array<string, mixed> $options
     */
    private function createContext(array $salesChannel, array $options): SalesChannelContext
    {
        $factory = static::getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');

        $salesChannelId = Uuid::randomHex();
        $salesChannel['id'] = $salesChannelId;
        $salesChannel['customerGroupId'] = TestDefaults::FALLBACK_CUSTOMER_GROUP;

        $salesChannelRepository->create([$salesChannel], Context::createDefaultContext());

        $context = $factory->create(Uuid::randomHex(), $salesChannelId, $options);

        $ruleLoader = static::getContainer()->get(CartRuleLoader::class);
        $ruleLoader->loadByToken($context, $context->getToken());

        return $context;
    }
}
