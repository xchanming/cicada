<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\DataAbstractionLayer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductCategoryDenormalizer;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(ProductCategoryDenormalizer::class)]
class ProductCategoryDenormalizerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Context $context;

    private Connection $connection;

    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->context = Context::createDefaultContext();
        $this->productRepository = static::getContainer()->get('product.repository');
    }

    public function testUpdateWithProductAddedCategoriesWillVariantGetSameCategories(): void
    {
        ['products' => $productFixture, 'categories' => $categoryIds] = $this->prepareData();

        $this->productRepository->update([
            [
                'id' => $productFixture['testable-product'],
                'categories' => \array_map(fn (string $categoryId) => ['id' => $categoryId], $categoryIds),
            ],
        ], $this->context);

        static::assertSame($categoryIds, $this->getProductCategoryList($productFixture['testable-product']));
        static::assertEquals(
            \count($categoryIds),
            $this->getCountRowsInProductCategoryTree($productFixture['testable-product'], $categoryIds)
        );

        static::assertSame($categoryIds, $this->getProductCategoryList($productFixture['variant-testable-product']));
        static::assertEquals(
            \count($categoryIds),
            $this->getCountRowsInProductCategoryTree($productFixture['variant-testable-product'], $categoryIds)
        );
    }

    /**
     * @return array<string>|null
     */
    private function getProductCategoryList(string $productId): ?array
    {
        $productRepository = static::getContainer()->get('product.repository');
        /** @var ProductEntity $testableProduct */
        $testableProduct = $productRepository->search(new Criteria([$productId]), $this->context)->first();

        $productCategoryIds = $testableProduct->getCategoryTree();
        if ($productCategoryIds !== null) {
            \sort($productCategoryIds);
        }

        return $productCategoryIds;
    }

    /**
     * @param list<string> $categoryIds
     */
    private function getCountRowsInProductCategoryTree(string $productId, array $categoryIds): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) as cnt FROM product_category_tree WHERE product_id = :productId AND category_id IN (:categoryIds)',
            [
                'productId' => Uuid::fromHexToBytes($productId),
                'categoryIds' => Uuid::fromHexToBytesList($categoryIds),
            ],
            ['categoryIds' => ArrayParameterType::BINARY]
        );
    }

    /**
     * @return array{products: array{product-with-category: string, testable-product: string, variant-testable-product: string}, categories: list<string>}
     */
    private function prepareData(): array
    {
        $ids = new IdsCollection();

        $products = [];

        $name = 'product-with-category';
        $builder = new ProductBuilder($ids, $name);
        $builder->price(200)
            ->categories(['cat1', 'cat2'])
            ->write(static::getContainer());
        /** @var array{id: string, children: array<int, array{id: string}>, categories: array<int, array{id: string, name:string}>} $product */
        $product = $builder->build();
        $products[$name] = $product['id'];
        /** @var list<string> $categories */
        $categories = \array_column($product['categories'], 'id');
        \sort($categories);

        $name = 'testable-product';
        $builder = new ProductBuilder($ids, $name);
        $builder->price(100)
            ->variant(
                (new ProductBuilder($ids, 'variant-testable-product'))
                ->price(100)->build()
            )
            ->write(static::getContainer());

        $product = $builder->build();
        $products[$name] = $product['id'];
        $products['variant-testable-product'] = $product['children'][0]['id'];

        static::assertEquals(
            0,
            $this->getCountRowsInProductCategoryTree($products['variant-testable-product'], $categories)
        );

        return [
            'products' => $products,
            'categories' => $categories,
        ];
    }
}
