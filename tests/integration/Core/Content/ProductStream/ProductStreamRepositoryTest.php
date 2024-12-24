<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ProductStream;

use Cicada\Core\Content\ProductStream\Aggregate\ProductStreamFilter\ProductStreamFilterCollection;
use Cicada\Core\Content\ProductStream\ProductStreamCollection;
use Cicada\Core\Content\ProductStream\ProductStreamEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductStreamRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<ProductStreamCollection>
     */
    private EntityRepository $repository;

    private Context $context;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product_stream.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testCreateEntity(): void
    {
        $id = Uuid::randomHex();
        $this->repository->upsert([['id' => $id, 'name' => 'Test stream']], $this->context);

        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search(new Criteria([$id]), $this->context)->get($id);
        static::assertTrue($entity->isInvalid());
        static::assertNull($entity->getApiFilter());
        static::assertSame('Test stream', $entity->getName());
        static::assertSame($id, $entity->getId());
    }

    public function testUpdateEntity(): void
    {
        $id = Uuid::randomHex();
        $this->repository->upsert([['id' => $id, 'name' => 'Test stream']], $this->context);
        $this->repository->upsert([['id' => $id, 'name' => 'New Name']], $this->context);

        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search(new Criteria([$id]), $this->context)->get($id);
        static::assertTrue($entity->isInvalid());
        static::assertNull($entity->getApiFilter());
        static::assertSame('New Name', $entity->getName());
        static::assertSame($id, $entity->getId());
    }

    public function testCreateEntityWithFilters(): void
    {
        $id = Uuid::randomHex();
        $this->repository->upsert([['id' => $id, 'name' => 'Test stream', 'filters' => [['type' => 'contains', 'field' => 'name', 'value' => 'awesome']]]], $this->context);

        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search(new Criteria([$id]), $this->context)->get($id);
        static::assertFalse($entity->isInvalid());
        static::assertNotNull($entity->getApiFilter());
        static::assertSame('Test stream', $entity->getName());
        static::assertSame($id, $entity->getId());
    }

    public function testCreateEntityWithMultiFilters(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'name' => 'Test stream',
            'filters' => [
                [
                    'type' => 'multi',
                    'operator' => 'OR',
                    'queries' => [
                        [
                            'type' => 'multi',
                            'operator' => 'AND',
                            'queries' => [
                                [
                                    'type' => 'multi',
                                    'operator' => 'OR',
                                    'queries' => [
                                        [
                                            'type' => 'equals',
                                            'field' => 'product.name',
                                            'value' => 'awesome',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->repository->upsert([$data], $this->context);

        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search(new Criteria([$id]), $this->context)->get($id);
        static::assertFalse($entity->isInvalid());
        static::assertNotNull($entity->getApiFilter());
        static::assertSame('Test stream', $entity->getName());
        static::assertSame($id, $entity->getId());
        static::assertEquals($data['filters'], $entity->getApiFilter());
    }

    public function testFetchFilters(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'name' => 'Test stream',
            'filters' => [
                [
                    'type' => 'multi',
                    'operator' => 'OR',
                    'queries' => [
                        [
                            'type' => 'multi',
                            'operator' => 'AND',
                            'queries' => [
                                [
                                    'type' => 'multi',
                                    'operator' => 'OR',
                                    'queries' => [
                                        [
                                            'type' => 'equals',
                                            'field' => 'product.name',
                                            'value' => 'awesome',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->repository->upsert([$data], $this->context);

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('filters');

        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search($criteria, $this->context)->get($id);

        /** @var ProductStreamFilterCollection $filters */
        $filters = $entity->getFilters();
        static::assertNotNull($filters);

        static::assertCount(4, $filters);
        static::assertCount(1, $filters->filterByProperty('field', 'product.name')->getElements());
        static::assertCount(3, $filters->filterByProperty('type', 'multi')->getElements());
        static::assertCount(1, $filters->filterByProperty('type', 'multi')->filterByProperty('operator', 'AND')->getElements());
        static::assertCount(2, $filters->filterByProperty('type', 'multi')->filterByProperty('operator', 'OR')->getElements());
    }

    public function testFetchWithQueriesFilter(): void
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'name' => 'Test stream',
            'filters' => [
                [
                    'type' => 'multi',
                    'operator' => 'OR',
                    'queries' => [
                        [
                            'type' => 'multi',
                            'operator' => 'AND',
                            'queries' => [
                                [
                                    'type' => 'multi',
                                    'operator' => 'OR',
                                    'queries' => [
                                        [
                                            'type' => 'equals',
                                            'field' => 'product.name',
                                            'value' => 'awesome',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->repository->upsert([$data], $this->context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product_stream.filters.queries.queries.queries.field', 'product.name'));
        /** @var ProductStreamEntity $entity */
        $entity = $this->repository->search($criteria, $this->context)->get($id);

        static::assertNotNull($entity);
        static::assertSame('Test stream', $entity->getName());
    }
}
