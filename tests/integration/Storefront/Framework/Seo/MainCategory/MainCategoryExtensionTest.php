<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Seo\MainCategory;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Seo\MainCategory\MainCategoryCollection;
use Cicada\Core\Content\Seo\MainCategory\MainCategoryEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('inventory')]
class MainCategoryExtensionTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepository;

    private EntityRepository $categoryRepository;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->categoryRepository = static::getContainer()->get('category.repository');
    }

    public function testMainCategoryLoaded(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $id = $this->createTestProduct();

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('mainCategories');

        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, $salesChannelContext->getContext())->first();

        static::assertNotNull($product->getMainCategories());
        static::assertInstanceOf(MainCategoryCollection::class, $product->getMainCategories());
        static::assertEmpty($product->getMainCategories());

        // update main category
        $categories = $this->categoryRepository->searchIds(new Criteria(), Context::createDefaultContext());

        $this->productRepository->update([
            [
                'id' => $id,
                'mainCategories' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'categoryId' => $categories->firstId(),
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $product = $this->productRepository->search($criteria, $salesChannelContext->getContext())
            ->getEntities()
            ->first();
        static::assertNotNull($product);

        $mainCategories = $product->getMainCategories();
        static::assertNotNull($mainCategories);
        static::assertInstanceOf(MainCategoryCollection::class, $mainCategories);
        static::assertCount(1, $mainCategories);

        $mainCategory = $mainCategories->filterBySalesChannelId($salesChannelId)->first();
        static::assertInstanceOf(MainCategoryEntity::class, $mainCategory);
        static::assertEquals($salesChannelId, $mainCategory->getSalesChannelId());
        static::assertEquals($categories->firstId(), $mainCategory->getCategoryId());
    }

    private function createTestProduct(): string
    {
        $id = Uuid::randomHex();
        $payload = [
            'id' => $id,
            'name' => 'foo bar',
            'manufacturer' => [
                'id' => Uuid::randomHex(),
                'name' => 'amazing brand',
            ],
            'productNumber' => 'P1234',
            'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 10,
                    'net' => 12,
                    'linked' => false,
                ],
            ],
            'stock' => 0,
        ];

        $this->productRepository->create([
            $payload,
        ], Context::createDefaultContext());

        return $id;
    }
}
