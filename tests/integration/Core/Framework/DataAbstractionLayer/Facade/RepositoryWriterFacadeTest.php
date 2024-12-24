<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Facade;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Api\Exception\MissingPrivilegeException;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Script\Execution\Script;
use Cicada\Core\Framework\Script\Execution\ScriptAppInformation;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\Framework\Test\Script\Execution\TestHook;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\Tax\TaxEntity;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class RepositoryWriterFacadeTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    private RepositoryWriterFacadeHookFactory $factory;

    private Context $context;

    protected function setUp(): void
    {
        $this->factory = static::getContainer()->get(RepositoryWriterFacadeHookFactory::class);
        $this->context = Context::createDefaultContext();
    }

    /**
     * @param array<int, mixed> $payload
     * @param callable(Context, ContainerInterface): void $expectation
     */
    #[DataProvider('testCases')]
    public function testFacade(array $payload, string $method, IdsCollection $ids, callable $expectation): void
    {
        $this->ids = $ids;
        $this->createProducts();

        $facade = $this->factory->factory(
            new TestHook('test', $this->context),
            new Script('test', '', new \DateTimeImmutable())
        );

        $facade->$method('product', $payload); /* @phpstan-ignore-line */

        $expectation($this->context, static::getContainer());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function testCases(): array
    {
        $ids = new IdsCollection();

        return [
            'testCreateViaUpsert' => [
                [
                    (new ProductBuilder($ids, 'p4'))
                    ->visibility()
                    ->price(300)
                    ->build(),
                ],
                'upsert',
                $ids,
                function (Context $context, ContainerInterface $container) use ($ids): void {
                    $productRepository = $container->get('product.repository');

                    $createdProduct = $productRepository->search(new Criteria([$ids->get('p4')]), $context)->first();

                    static::assertInstanceOf(ProductEntity::class, $createdProduct);
                },
            ],
            'testUpdateViaUpsert' => [
                [
                    [
                        'id' => $ids->get('p2'),
                        'active' => true,
                    ],
                ],
                'upsert',
                $ids,
                function (Context $context, ContainerInterface $container) use ($ids): void {
                    $productRepository = $container->get('product.repository');

                    $updated = $productRepository->search(new Criteria([$ids->get('p2')]), $context)->first();

                    static::assertInstanceOf(ProductEntity::class, $updated);
                    static::assertTrue($updated->getActive());
                },
            ],
            'testDelete' => [
                [
                    ['id' => $ids->get('p2')],
                ],
                'delete',
                $ids,
                function (Context $context, ContainerInterface $container) use ($ids): void {
                    $productRepository = $container->get('product.repository');

                    $deleted = $productRepository->search(new Criteria([$ids->get('p2')]), $context)->first();

                    static::assertNull($deleted);
                },
            ],
        ];
    }

    public function testSync(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $facade = $this->factory->factory(
            new TestHook('test', $this->context),
            new Script('test', '', new \DateTimeImmutable())
        );

        $facade->sync([
            [
                'entity' => 'product',
                'action' => 'upsert',
                'payload' => [
                    (new ProductBuilder($this->ids, 'p4'))
                        ->visibility()
                        ->price(300)
                        ->build(),
                    [
                        'id' => $this->ids->get('p2'),
                        'active' => true,
                    ],
                ],
            ],
            [
                'entity' => 'product',
                'action' => 'delete',
                'payload' => [
                    ['id' => $this->ids->get('p3')],
                ],
            ],
        ]);

        $productRepository = static::getContainer()->get('product.repository');

        $createdProduct = $productRepository->search(new Criteria([$this->ids->get('p4')]), $this->context)->first();
        static::assertInstanceOf(ProductEntity::class, $createdProduct);

        $updated = $productRepository->search(new Criteria([$this->ids->get('p2')]), $this->context)->first();
        static::assertInstanceOf(ProductEntity::class, $updated);
        static::assertTrue($updated->getActive());

        $deleted = $productRepository->search(new Criteria([$this->ids->get('p3')]), $this->context)->first();
        static::assertNull($deleted);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    #[DataProvider('withoutPermissionsCases')]
    public function testWithoutPermission(array $arguments, string $method, IdsCollection $ids): void
    {
        $this->ids = $ids;
        $this->createProducts();

        $appInfo = $this->installApp(__DIR__ . '/_fixtures/apps/withoutProductPermission');

        $facade = $this->factory->factory(
            new TestHook('test', Context::createDefaultContext()),
            new Script('test', '', new \DateTimeImmutable(), $appInfo)
        );

        static::expectException(MissingPrivilegeException::class);
        $facade->$method(...$arguments); /* @phpstan-ignore-line */
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function withoutPermissionsCases(): array
    {
        $ids = new IdsCollection();

        return [
            'testUpsert' => [
                ['product', [['id' => $ids->get('p2'), 'active' => true]]],
                'upsert',
                $ids,
            ],
            'testDelete' => [
                ['product', [['id' => $ids->get('p3')]]],
                'delete',
                $ids,
            ],
            'testSync' => [
                [
                    [
                        [
                            'entity' => 'product',
                            'action' => 'delete',
                            'payload' => [['id' => $ids->get('p3')]],
                        ],
                    ],
                ],
                'sync',
                $ids,
            ],
        ];
    }

    public function testIntegrationCreateCase(): void
    {
        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $hook = new TestHook(
            'writer-create',
            $this->context,
            [],
            [
                RepositoryWriterFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        $taxRepository = static::getContainer()->get('tax.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'new tax'));

        $createdTax = $taxRepository->search($criteria, $this->context)->first();
        static::assertInstanceOf(TaxEntity::class, $createdTax);
    }

    public function testIntegrationUpdateCase(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $hook = new TestHook(
            'writer-update',
            $this->context,
            [
                'productId' => $this->ids->get('p2'),
            ],
            [
                RepositoryWriterFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        $productRepository = static::getContainer()->get('product.repository');

        $updated = $productRepository->search(new Criteria([$this->ids->get('p2')]), $this->context)->first();
        static::assertInstanceOf(ProductEntity::class, $updated);
        static::assertTrue($updated->getActive());
    }

    public function testIntegrationDeleteCase(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $hook = new TestHook(
            'writer-delete',
            $this->context,
            [
                'productId' => $this->ids->get('p3'),
            ],
            [
                RepositoryWriterFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        $productRepository = static::getContainer()->get('product.repository');

        $deleted = $productRepository->search(new Criteria([$this->ids->get('p3')]), $this->context)->first();
        static::assertNull($deleted);
    }

    public function testIntegrationSyncCase(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $hook = new TestHook(
            'writer-sync',
            $this->context,
            [
                'updateProductId' => $this->ids->get('p2'),
                'deleteProductId' => $this->ids->get('p3'),
            ],
            [
                RepositoryWriterFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        $productRepository = static::getContainer()->get('product.repository');

        $updated = $productRepository->search(new Criteria([$this->ids->get('p2')]), $this->context)->first();
        static::assertInstanceOf(ProductEntity::class, $updated);
        static::assertTrue($updated->getActive());

        $deleted = $productRepository->search(new Criteria([$this->ids->get('p3')]), $this->context)->first();
        static::assertNull($deleted);
    }

    private function createProducts(): void
    {
        $taxId = $this->getExistingTaxId();
        $this->ids->set('t1', $taxId);

        $product1 = (new ProductBuilder($this->ids, 'p1'))
            ->price(100)
            ->visibility()
            ->manufacturer('m1')
            ->variant(
                (new ProductBuilder($this->ids, 'v1.1'))
                    ->build()
            );

        $product2 = (new ProductBuilder($this->ids, 'p2'))
            ->price(200)
            ->visibility()
            ->active(false);

        $product3 = (new ProductBuilder($this->ids, 'p3'))
            ->visibility()
            ->price(300);

        static::getContainer()->get('product.repository')->create([
            $product1->build(),
            $product2->build(),
            $product3->build(),
        ], $this->context);
    }

    private function installApp(string $appDir): ScriptAppInformation
    {
        $this->loadAppsFromDir($appDir);

        /** @var EntityRepository<AppCollection> $repository */
        $repository = static::getContainer()->get('app.repository');
        $app = $repository
            ->search(new Criteria(), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($app);

        return new ScriptAppInformation(
            $app->getId(),
            $app->getName(),
            $app->getIntegrationId()
        );
    }

    private function getExistingTaxId(): string
    {
        /** @var EntityRepository $taxRepository */
        $taxRepository = static::getContainer()->get('tax.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Standard rate'));

        $taxId = $taxRepository->searchIds($criteria, $this->context)->firstId();

        static::assertNotNull($taxId);

        return $taxId;
    }
}
