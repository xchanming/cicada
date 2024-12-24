<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\ProductCrossSellingSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingCollection;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts\ProductCrossSellingAssignedProductsEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductCrossSellingSerializerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testOnlySupportsProductCrossSelling(): void
    {
        /** @var EntityRepository $assignedProductsRepository */
        $assignedProductsRepository = static::getContainer()->get('product_cross_selling_assigned_products.repository');

        $serializer = new ProductCrossSellingSerializer($assignedProductsRepository);

        static::assertTrue($serializer->supports(ProductCrossSellingDefinition::ENTITY_NAME), 'should support product cross selling');

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();
            if ($entity !== ProductCrossSellingDefinition::ENTITY_NAME) {
                static::assertFalse(
                    $serializer->supports($definition->getEntityName()),
                    ProductCrossSellingSerializer::class . ' should not support ' . $entity
                );
            }
        }
    }

    public function testCrossSellingSerialize(): void
    {
        $crossSelling = $this->getProductCrossSelling();

        $assignedProductsRepository = static::getContainer()->get('product_cross_selling_assigned_products.repository');
        $productCrossSellingDefinition = static::getContainer()->get(ProductCrossSellingDefinition::class);

        $serializer = new ProductCrossSellingSerializer($assignedProductsRepository);
        $serializer->setRegistry(static::getContainer()->get(SerializerRegistry::class));

        $serialized = iterator_to_array($serializer->serialize(new Config([], [], []), $productCrossSellingDefinition, $crossSelling));

        static::assertNotEmpty($serialized);

        $assignedProducts = $crossSelling->getAssignedProducts();
        static::assertNotNull($assignedProducts);
        $assignedProducts->sort(fn (ProductCrossSellingAssignedProductsEntity $a, ProductCrossSellingAssignedProductsEntity $b) => $a->getPosition() <=> $b->getPosition());
        $productsIds = $assignedProducts->map(fn (ProductCrossSellingAssignedProductsEntity $assignedProductsEntity) => $assignedProductsEntity->getProductId());

        static::assertSame($crossSelling->getId(), $serialized['id']);
        static::assertSame($crossSelling->getProductId(), $serialized['productId']);
        static::assertSame(implode('|', $productsIds), $serialized['assignedProducts']);

        $deserialized = $serializer->deserialize(new Config([], [], []), $productCrossSellingDefinition, $serialized);

        static::assertIsArray($deserialized);
        static::assertSame($crossSelling->getId(), $deserialized['id']);
        static::assertSame($crossSelling->getProductId(), $deserialized['productId']);
        static::assertSame(array_values($productsIds), array_column($deserialized['assignedProducts'], 'productId'));
    }

    private function getProductCrossSelling(): ProductCrossSellingEntity
    {
        $ids = new IdsCollection();

        $data = [
            (new ProductBuilder($ids, 'a'))->price(15, 10)->visibility()->build(),
            (new ProductBuilder($ids, 'b'))->price(15, 10)->visibility()->build(),
            (new ProductBuilder($ids, 'c'))->price(15, 10)->visibility()->build(),
            (new ProductBuilder($ids, 'd'))->price(15, 10)->visibility()->build(),
            (new ProductBuilder($ids, 'e'))->price(15, 10)->visibility()->build(),
        ];

        $productRepository = static::getContainer()->get('product.repository');
        $productRepository->create($data, Context::createDefaultContext());

        $crossSellingId = Uuid::randomHex();

        $crossSelling = [
            'id' => $crossSellingId,
            'productId' => $ids->get('a'),
            'active' => true,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'test cross selling',
                ],
            ],
            'type' => 'productList',
            'position' => 1,
            'limit' => 500,
            'sortBy' => 'name',
            'sortDirection' => 'ASC',
            'assignedProducts' => [
                ['productId' => $ids->get('b'), 'position' => 0],
                ['productId' => $ids->get('c'), 'position' => 1],
                ['productId' => $ids->get('d'), 'position' => 2],
                ['productId' => $ids->get('e'), 'position' => 3],
            ],
        ];

        /** @var EntityRepository<ProductCrossSellingCollection> $crossSellingRepository */
        $crossSellingRepository = static::getContainer()->get('product_cross_selling.repository');
        $crossSellingRepository->create([$crossSelling], Context::createDefaultContext());

        $criteria = new Criteria([$crossSellingId]);
        $criteria->addAssociation('assignedProducts');

        $crossSellingEntity = $crossSellingRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($crossSellingEntity);

        return $crossSellingEntity;
    }
}
