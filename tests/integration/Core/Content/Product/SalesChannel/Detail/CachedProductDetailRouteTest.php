<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Detail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Events\ProductDetailRouteCacheTagsEvent;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Tax\TaxEntity;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedProductDetailRouteTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const ALL_TAG = 'test-tag';

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        parent::setUp();

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(\Closure $closure, int $calls, bool $isTestingWithVariant = false): void
    {
        static::getContainer()->get('cache.object')->invalidateTags([self::ALL_TAG]);

        static::getContainer()->get('event_dispatcher')
            ->addListener(ProductDetailRouteCacheTagsEvent::class, static function (ProductDetailRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            });

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        static::getContainer()
            ->get('event_dispatcher')
            ->addListener(ProductDetailRouteCacheTagsEvent::class, $listener);

        $productId = Uuid::randomHex();
        $propertyId = Uuid::randomHex();
        $cmsPageId = $this->createCmsPage('product_detail');

        $this->createProduct([
            'id' => $productId,
            'properties' => [
                ['id' => $propertyId, 'name' => 'red', 'group' => ['name' => 'color']],
            ],
            'cmsPageId' => $cmsPageId,
        ]);

        if ($isTestingWithVariant) {
            $variantId = Uuid::randomHex();
            $variantPropertyId = Uuid::randomHex();

            $this->createProduct([
                'id' => $variantId,
                'parentId' => $productId,
                'name' => 'test variant',
                'productNumber' => 'test variant',
                'options' => [
                    ['id' => $variantPropertyId, 'name' => 'red', 'group' => ['name' => 'color']],
                ],
            ]);

            $productId = $variantId;
            $propertyId = $variantPropertyId;
        }

        $route = static::getContainer()->get(ProductDetailRoute::class);
        $route->load($productId, new Request(), $this->context, new Criteria());
        $route->load($productId, new Request(), $this->context, new Criteria());

        $closure($propertyId, static::getContainer());

        $route->load($productId, new Request(), $this->context, new Criteria());
        $route->load($productId, new Request(), $this->context, new Criteria());
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Cache is invalidated if the updated property is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $update = ['id' => $propertyId, 'name' => 'yellow'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache is invalidated if the deleted property is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $delete = ['id' => $propertyId];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache is invalidated if the updated options is used by the product' => [
            function (string $propertyId, ContainerInterface $container): void {
                $update = ['id' => $propertyId, 'name' => 'yellow'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            2,
            true,
        ];

        yield 'Cache is not invalidated if the updated property is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property2'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );
                $update = ['id' => $ids->get('property2'), 'name' => 'XL'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache is not invalidated if the deleted property is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property3'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );

                $delete = ['id' => $ids->get('property3')];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache is not invalidated if the updated options is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property2'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );
                $update = ['id' => $ids->get('property2'), 'name' => 'XL'];
                $container->get('property_group_option.repository')->update([$update], Context::createDefaultContext());
            },
            1,
            true,
        ];

        yield 'Cache is not invalidated if the deleted options is not used by the product' => [
            function (string $propertyId, ContainerInterface $container) use ($ids): void {
                $container->get('property_group_option.repository')->create(
                    [
                        ['id' => $ids->get('property3'), 'name' => 'L', 'group' => ['name' => 'size']],
                    ],
                    Context::createDefaultContext()
                );

                $delete = ['id' => $ids->get('property3')];
                $container->get('property_group_option.repository')->delete([$delete], Context::createDefaultContext());
            },
            1,
            true,
        ];
    }

    /**
     * @param array<mixed> $data
     */
    private function createProduct(array $data = []): void
    {
        $ids = new IdsCollection();

        $tax = $this->context->getTaxRules()->first();

        static::assertInstanceOf(TaxEntity::class, $tax);

        $product = array_merge(
            [
                'name' => 'test',
                'productNumber' => 'test',
                'stock' => 10,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
                'tax' => ['id' => $tax->getId(), 'name' => 'test', 'taxRate' => 15],
                'visibilities' => [[
                    'salesChannelId' => $this->context->getSalesChannelId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ]],
            ],
            $data
        );

        static::getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());
    }

    private function createCmsPage(string $type): string
    {
        $cmsPageId = Uuid::randomHex();

        $cmsPage = [
            'id' => $cmsPageId,
            'name' => 'test page',
            'type' => $type,
        ];

        static::getContainer()->get('cms_page.repository')->create([$cmsPage], Context::createDefaultContext());

        return $cmsPageId;
    }
}
