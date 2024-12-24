<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Seo\DataAbstractionLayer\Indexing;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Content\Seo\SeoUrlUpdater;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\App\Template\TemplateCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\InheritanceUpdater;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteTypeIntendException;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[Group('slow')]
class SeoUrlIndexerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use QueueTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    private EntityRepository $templateRepository;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateRepository = static::getContainer()->get('seo_url_template.repository');
        $this->productRepository = static::getContainer()->get('product.repository');

        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM `sales_channel`');
    }

    public function testDefaultNew(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'productNumber' => 'P1'], $salesChannelId);

        $product = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($product);
        $productSeoUrls = $product->getSeoUrls();
        static::assertNotNull($productSeoUrls);
        $canonicalUrl = $productSeoUrls->first();
        static::assertNotNull($canonicalUrl);
        static::assertSame('awesome-product/P1', $canonicalUrl->getSeoPathInfo());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);

        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', false);

        static::assertNotNull($canonicalUrls->first());
        static::assertSame($canonicalUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(0, $nonCanonicals);
        static::assertCount(1, $seoUrls);
    }

    public function testDefaultUpdateSamePath(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();

        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'productNumber' => 'P1'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'description' => 'this description should not matter', 'productNumber' => 'P1'], $salesChannelId);

        $product = $this->productRepository->search(
            $this->getCriteria($id, $salesChannelId),
            $salesChannelContext->getContext()
        )
            ->getEntities()
            ->first();
        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());

        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $product->getSeoUrls()->first();
        static::assertNotNull($seoUrl);
        static::assertSame('awesome-product/P1', $seoUrl->getSeoPathInfo());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', false);

        static::assertNotNull($canonicalUrls->first());
        static::assertSame($seoUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(0, $nonCanonicals);
        static::assertCount(1, $seoUrls);
    }

    public function testDefaultUpdateDifferentPath(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();

        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'productNumber' => 'P1'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product v2', 'productNumber' => 'P1'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product v3', 'productNumber' => 'P1'], $salesChannelId);

        $product = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());

        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $product->getSeoUrls()->first();
        static::assertSame('awesome-product-v3/P1', $seoUrl->getSeoPathInfo());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', null);

        static::assertNotNull($canonicalUrls->first());
        static::assertSame($seoUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(2, $nonCanonicals);
        static::assertCount(3, $seoUrls);
    }

    public function testCustomNew(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertTemplate([
            'id' => $id,
            'salesChannelId' => $salesChannelId,
            'template' => 'foo/{{ product.name }}/bar',
        ]);

        $this->upsertProduct(['id' => $id, 'name' => 'awesome product'], $salesChannelId);

        $first = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($first);
        static::assertInstanceOf(SeoUrlCollection::class, $first->getSeoUrls());

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getSeoUrls();
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $seoUrls->first();
        static::assertNotNull($seoUrl);
        static::assertSame($first->getId(), $seoUrl->getForeignKey());
        static::assertSame(ProductPageSeoUrlRoute::ROUTE_NAME, $seoUrl->getRouteName());
        static::assertSame('/detail/' . $id, $seoUrl->getPathInfo());
        static::assertSame('foo/awesome-product/bar', $seoUrl->getSeoPathInfo());
        static::assertTrue($seoUrl->getIsCanonical());
    }

    public function testCustomUpdateSamePath(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertTemplate([
            'id' => $id,
            'salesChannelId' => $salesChannelId,
            'template' => 'foo/{{ product.name}}/bar',
        ]);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'description' => 'should not matter'], $salesChannelId);

        $first = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($first);

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getSeoUrls();
        static::assertNotNull($seoUrls);
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $seoUrls->first();
        static::assertSame('foo/awesome-product/bar', $seoUrl->getSeoPathInfo());
        static::assertTrue($seoUrl->getIsCanonical());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', false);

        static::assertNotNull($seoUrl);
        static::assertNotNull($canonicalUrls->first());
        static::assertSame($seoUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(0, $nonCanonicals);
        static::assertCount(1, $seoUrls);
    }

    public function testCustomUpdateDifferentPath(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertTemplate([
            'id' => $id,
            'salesChannelId' => $salesChannelId,
            'template' => 'foo/{{ product.name }}/bar',
        ]);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product improved'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product improved again'], $salesChannelId);

        $first = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($first);
        static::assertNotNull($first->getSeoUrls());

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getSeoUrls();
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $seoUrls->first();
        static::assertSame('foo/awesome-product-improved-again/bar', $seoUrl->getSeoPathInfo());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', null);

        static::assertNotNull($seoUrl);
        static::assertNotNull($canonicalUrls->first());
        static::assertSame($seoUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(2, $nonCanonicals);
        static::assertCount(3, $seoUrls);
    }

    public function testUpdateWithUpdatedTemplate(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertTemplate([
            'id' => $id,
            'salesChannelId' => $salesChannelId,
            'template' => 'foo/{{ product.name }}/bar',
        ]);

        $this->upsertProduct(['id' => $id, 'name' => 'awesome product'], $salesChannelId);
        $this->upsertTemplate([
            'id' => $id,
            'salesChannelId' => $salesChannelId,
            'template' => 'bar/{{ product.name }}/baz',
        ]);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product improved'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product improved'], $salesChannelId);

        $first = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();

        static::assertNotNull($first);
        static::assertNotNull($first->getSeoUrls());

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getSeoUrls();
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $seoUrls->first();
        static::assertSame($first->getId(), $seoUrl->getForeignKey());
        static::assertSame(ProductPageSeoUrlRoute::ROUTE_NAME, $seoUrl->getRouteName());
        static::assertSame('/detail/' . $id, $seoUrl->getPathInfo());
        static::assertSame('bar/awesome-product-improved/baz', $seoUrl->getSeoPathInfo());
        static::assertTrue($seoUrl->getIsCanonical());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        $canonicalUrls = $seoUrls->filterByProperty('isCanonical', true);
        $nonCanonicals = $seoUrls->filterByProperty('isCanonical', null);

        static::assertNotNull($seoUrl);
        static::assertNotNull($canonicalUrls->first());
        static::assertSame($seoUrl->getId(), $canonicalUrls->first()->getId());

        static::assertCount(1, $canonicalUrls);
        static::assertCount(1, $nonCanonicals);
        static::assertCount(2, $seoUrls);

        static::assertNotNull($nonCanonicals->first());
        static::assertSame('foo/awesome-product/bar', $nonCanonicals->first()->getSeoPathInfo());
    }

    public function testIsMarkedAsDeleted(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = Uuid::randomHex();
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product', 'productNumber' => 'P1'], $salesChannelId);
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product v2', 'productNumber' => 'P1'], $salesChannelId);

        $product = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $salesChannelContext->getContext())
            ->getEntities()
            ->first();

        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $product->getSeoUrls();
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $seoUrls->first();
        static::assertSame('awesome-product-v2/P1', $seoUrl->getSeoPathInfo());
        static::assertFalse($seoUrl->getIsDeleted());

        $this->productRepository->delete([['id' => $id]], $salesChannelContext->getContext());

        $seoUrls = $this->getSeoUrls($salesChannelId, $id);
        static::assertCount(2, $seoUrls);
        static::assertCount(2, $seoUrls->filterByProperty('isDeleted', true));
        static::assertCount(0, $seoUrls->filterByProperty('isDeleted', false));
    }

    public function testAutoSlugify(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $this->upsertTemplate([
            'id' => Uuid::randomHex(),
            'salesChannelId' => $salesChannelId,
            'template' => '{{ product.name }}', // no slugify!
        ]);

        $id = Uuid::randomHex();
        $this->upsertProduct(['id' => $id, 'name' => 'foo bar'], $salesChannelId);

        $context = $salesChannelContext->getContext();

        $product = $this->productRepository->search($this->getCriteria($id, $salesChannelId), $context)
            ->getEntities()
            ->first();

        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());
        /** @var SeoUrlEntity $seoUrl */
        $seoUrl = $product->getSeoUrls()->first();
        static::assertNotNull($seoUrl);
        static::assertSame('foo-bar', $seoUrl->getSeoPathInfo());
        static::assertFalse($seoUrl->getIsDeleted());
    }

    public function testEntityIsSkippedOnRuntimeError(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $this->upsertTemplate([
            'id' => Uuid::randomHex(),
            'salesChannelId' => $salesChannelId,
            'template' => '{{ product.customFields.foo }}', // this throws a runtime error, because attributes is null
        ]);

        $id = Uuid::randomHex();
        $this->upsertProduct([
            'id' => $id,
            'name' => 'foo',
        ], $salesChannelId);

        $context = $salesChannelContext->getContext();

        $criteria = (new Criteria([$id]))->addAssociation('seoUrls');
        $product = $this->productRepository->search($criteria, $context)
            ->getEntities()
            ->first();
        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());

        $seoUrls = $product->getSeoUrls()->filterBySalesChannelId($salesChannelId);
        static::assertEmpty($seoUrls);

        $this->upsertProduct([
            'id' => $id,
            'name' => 'foo',
            'customFields' => [
                'foo' => 'bar',
            ],
        ], $salesChannelId);

        $criteria = (new Criteria([$id]))->addAssociation('seoUrls');
        $product = $this->productRepository->search($criteria, $context)
            ->getEntities()
            ->first();
        static::assertNotNull($product);
        static::assertNotNull($product->getSeoUrls());
        $seoUrls = $product->getSeoUrls()->filterBySalesChannelId($salesChannelId);

        static::assertNotEmpty($seoUrls);
    }

    public function testMultiCreate(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $products = [
            [
                'id' => $id1,
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'amazing brand',
                ],
                'name' => 'foo',
                'productNumber' => 'P1',
                'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 12, 'linked' => false]],
                'stock' => 0,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
            [
                'id' => $id2,
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'amazing brand 2',
                ],
                'name' => 'bar',
                'productNumber' => 'P2',
                'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 12, 'linked' => false]],
                'stock' => 0,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ];
        $this->productRepository->upsert($products, Context::createDefaultContext());

        $criteria = new Criteria([$id1, $id2]);
        $criteria->addAssociation('seoUrls');
        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        /** @var ProductEntity $first */
        $first = $products->first();
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getSeoUrls();
        static::assertCount(1, $seoUrls);

        /** @var ProductEntity $last */
        $last = $products->last();
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $last->getSeoUrls();
        static::assertCount(1, $seoUrls);
    }

    public function testInheritance(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $parentId = Uuid::randomHex();
        $child1Id = Uuid::randomHex();
        $child2Id = Uuid::randomHex();

        $products = [
            [
                'id' => $parentId,
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'amazing brand',
                ],
                'name' => 'foo',
                'productNumber' => 'P1',
                'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 12, 'linked' => false]],
                'stock' => 0,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
            [
                'id' => $child1Id,
                'parentId' => $parentId,
                'productNumber' => 'C1',
                'stock' => 0,
            ],
            [
                'id' => $child2Id,
                'parentId' => $parentId,
                'productNumber' => 'C2',
                'stock' => 0,
            ],
        ];

        $context = Context::createDefaultContext();
        $this->productRepository->upsert($products, $context);

        $this->runWorker();

        // update parent
        $update = [
            'id' => $parentId,
            'name' => 'updated',
        ];

        $this->productRepository->update([$update], Context::createDefaultContext());
        $this->runWorker();

        $this->runWorker();

        $criteria = new Criteria([$parentId, $child1Id, $child2Id]);
        $criteria->addAssociation('seoUrls');
        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        /** @var ProductEntity $parent */
        $parent = $products->get($parentId);
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $parent->getSeoUrls();
        static::assertCount(2, $seoUrls);
        /** @var SeoUrlEntity $canonical */
        $canonical = $seoUrls->filterByProperty('isCanonical', true)->first();
        static::assertNotNull($canonical);
        static::assertSame('updated/P1', $canonical->getSeoPathInfo());

        /** @var ProductEntity $child1 */
        $child1 = $products->get($child1Id);
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $child1->getSeoUrls();
        static::assertCount(2, $seoUrls);
        $canonical = $seoUrls->filterByProperty('isCanonical', true)->first();
        static::assertNotNull($canonical);
        static::assertSame('updated/C1', $canonical->getSeoPathInfo());

        /** @var ProductEntity $child2 */
        $child2 = $products->get($child2Id);
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $child2->getSeoUrls();
        static::assertCount(2, $seoUrls);
        $canonical = $seoUrls->filterByProperty('isCanonical', true)->first();
        static::assertNotNull($canonical);
        static::assertSame('updated/C2', $canonical->getSeoPathInfo());
    }

    public function testIndex(?int $seoUrlCount = 1): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $productDefinition = static::getContainer()->get(ProductDefinition::class);
        $writer = static::getContainer()->get(EntityWriter::class);

        $id = Uuid::randomHex();
        $products = [
            [
                'id' => $id,
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'amazing brand',
                ],
                'name' => 'foo',
                'productNumber' => 'P1',
                'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 12, 'linked' => false]],
                'stock' => 0,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ];

        // the writer does not fire events, so seo urls are not created automatically
        $writer->insert($productDefinition, $products, WriteContext::createFromContext(Context::createDefaultContext()));

        // Builds the index for visibilities
        static::getContainer()->get(InheritanceUpdater::class)->update('product', [$id], Context::createDefaultContext());

        static::getContainer()
            ->get(SeoUrlUpdater::class)
            ->update(ProductPageSeoUrlRoute::ROUTE_NAME, [$id]);

        /** @var EntityRepository $productRepo */
        $productRepo = static::getContainer()->get('product.repository');

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('seoUrls');

        /** @var ProductEntity $product */
        $product = $productRepo->search($criteria, Context::createDefaultContext())->first();
        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $product->getSeoUrls();
        static::assertInstanceOf(SeoUrlCollection::class, $seoUrls);
        /** @var int $seoUrlCount */
        static::assertCount($seoUrlCount, $seoUrls);
    }

    public function testIndexWithEmptySeoUrlTemplate(): void
    {
        $templateRepository = static::getContainer()->get('seo_url_template.repository');

        /** @var string[] $ids */
        $ids = $templateRepository->searchIds(new Criteria(), Context::createDefaultContext())->getIds();

        /** @var TemplateCollection $templates */
        $templates = $templateRepository->search(new Criteria($ids), Context::createDefaultContext())->getEntities();

        foreach ($templates as $template) {
            $template->setTemplate('');
        }

        $data = array_map(static fn (string $templateId): array => [
            'id' => $templateId,
            'template' => null,
        ], $templates->getIds());

        $templateRepository->upsert(array_values($data), Context::createDefaultContext());

        $this->testIndex(0);
    }

    private function getSeoUrls(string $salesChannelId, string $productId): SeoUrlCollection
    {
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('seo_url.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('foreignKey', $productId));

        /** @var SeoUrlCollection $seoUrlCollection */
        $seoUrlCollection = $repo->search($criteria, Context::createDefaultContext())->getEntities();

        static::assertNotNull($seoUrlCollection);

        return $seoUrlCollection;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertTemplate(array $data): void
    {
        $seoUrlTemplateDefaults = [
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'entityName' => static::getContainer()->get(ProductDefinition::class)->getEntityName(),
            'routeName' => ProductPageSeoUrlRoute::ROUTE_NAME,
        ];
        $seoUrlTemplate = array_merge($seoUrlTemplateDefaults, $data);
        $this->templateRepository->upsert([$seoUrlTemplate], Context::createDefaultContext());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertProduct(array $data, string $salesChannelId): void
    {
        $defaults = [
            'manufacturer' => [
                'id' => Uuid::randomHex(),
                'name' => 'amazing brand',
            ],
            'productNumber' => Uuid::randomHex(),
            'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 12, 'linked' => false]],
            'stock' => 0,
            'visibilities' => [
                [
                    'salesChannelId' => $salesChannelId,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $data = array_merge($defaults, $data);

        try {
            $this->productRepository->create([$data], Context::createDefaultContext());
        } catch (WriteTypeIntendException) {
            unset($data['visibilities']);
            $this->productRepository->upsert([$data], Context::createDefaultContext());
        }
    }

    private function getCriteria(string $productId, ?string $salesChannelId = null, string $languageId = Defaults::LANGUAGE_SYSTEM): Criteria
    {
        $criteria = new Criteria([$productId]);
        $seoUrlCriteria = $criteria->getAssociation('seoUrls');
        $seoUrlCriteria->addFilter(new EqualsFilter('isCanonical', true));
        $seoUrlCriteria->addFilter(new EqualsFilter('languageId', $languageId));
        $seoUrlCriteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        return $criteria;
    }
}
