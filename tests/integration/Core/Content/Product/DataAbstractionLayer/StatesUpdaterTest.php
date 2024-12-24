<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Cicada\Core\Content\Product\DataAbstractionLayer\StatesUpdater;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('inventory')]
class StatesUpdaterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;

    private StatesUpdater $statesUpdater;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->statesUpdater = static::getContainer()->get(StatesUpdater::class);
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testUpdateProductStates(): void
    {
        $ids = new IdsCollection();
        $this->prepareProducts($ids);

        $productIds = $ids->prefixed('product-');
        $this->statesUpdater->update($productIds, Context::createDefaultContext());

        $products = $this->productRepository
            ->search(new Criteria($productIds), Context::createDefaultContext())
            ->getEntities();

        $product1 = $products->get($ids->get('product-1'));
        $product2 = $products->get($ids->get('product-2'));
        $product3 = $products->get($ids->get('product-3'));

        static::assertInstanceOf(ProductEntity::class, $product1);
        static::assertInstanceOf(ProductEntity::class, $product2);
        static::assertInstanceOf(ProductEntity::class, $product3);
        static::assertEquals([State::IS_DOWNLOAD], $product1->getStates());
        static::assertEquals([State::IS_PHYSICAL], $product2->getStates());
        static::assertEquals([State::IS_PHYSICAL], $product3->getStates());
    }

    public function prepareProducts(IdsCollection $ids): void
    {
        $products = [
            (new ProductBuilder($ids, 'product-1'))
                ->price(1.0)
                ->add('downloads', [
                    [
                        'media' => [
                            'fileName' => 'foo',
                            'fileExtension' => 'bar',
                            'private' => true,
                        ],
                    ],
                ])
                ->build(),
            (new ProductBuilder($ids, 'product-2'))
                ->price(1.0)
                ->build(),
            (new ProductBuilder($ids, 'product-3'))
                ->price(1.0)
                ->build(),
        ];

        $context = Context::createDefaultContext();
        $context->addExtension(EntityIndexerRegistry::EXTENSION_INDEXER_SKIP, new ArrayEntity(['skips' => [ProductIndexer::STATES_UPDATER]]));

        $this->productRepository->create($products, $context);

        $this->connection->executeStatement(
            'UPDATE `product` SET `states` = :states WHERE `id` IN (:ids)',
            [
                'states' => json_encode([State::IS_PHYSICAL]),
                'ids' => Uuid::fromHexToBytesList([$ids->get('product-1'), $ids->get('product-2')]),
            ],
            ['ids' => ArrayParameterType::BINARY]
        );
    }
}
