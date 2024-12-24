<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Repository;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductTagTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = static::getContainer()->get('product.repository');
    }

    public function testEqualsAnyFilter(): void
    {
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();

        $red = Uuid::randomHex();
        $green = Uuid::randomHex();
        $blue = Uuid::randomHex();
        $notAssigned = Uuid::randomHex();

        $this->createProduct($id1, [
            ['id' => $red, 'name' => 'red'],
            ['id' => $blue, 'name' => 'blue'],
        ]);

        $this->createProduct($id2, [
            ['id' => $green, 'name' => 'green'],
            ['id' => $red, 'name' => 'red'],
        ]);

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('product.tagIds', [$red]));
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertContains($id1, $ids->getIds());
        static::assertContains($id2, $ids->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('product.tagIds', [$green]));
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertNotContains($id1, $ids->getIds());
        static::assertContains($id2, $ids->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('product.tagIds', [$notAssigned]));
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertNotContains($id1, $ids->getIds());
        static::assertNotContains($id2, $ids->getIds());
    }

    public function testNotEqualsAnyFilter(): void
    {
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();

        $red = Uuid::randomHex();
        $green = Uuid::randomHex();
        $blue = Uuid::randomHex();
        $notAssigned = Uuid::randomHex();

        $this->createProduct($id1, [
            ['id' => $red, 'name' => 'red'],
            ['id' => $blue, 'name' => 'blue'],
        ]);

        $this->createProduct($id2, [
            ['id' => $green, 'name' => 'green'],
            ['id' => $red, 'name' => 'red'],
        ]);

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsAnyFilter('product.tagIds', [$notAssigned]),
            ])
        );
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertContains($id1, $ids->getIds());
        static::assertContains($id2, $ids->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsAnyFilter('product.tagIds', [$green]),
            ])
        );
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertContains($id1, $ids->getIds());
        static::assertNotContains($id2, $ids->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsAnyFilter('product.tagIds', [$notAssigned]),
            ])
        );
        $ids = $this->repository->searchIds($criteria, $context);

        static::assertContains($id1, $ids->getIds());
        static::assertContains($id2, $ids->getIds());
    }

    /**
     * @param list<array<string, string>> $tags
     */
    private function createProduct(string $id, array $tags): void
    {
        $data = [
            'id' => $id,
            'name' => 'test',
            'productNumber' => Uuid::randomHex(),
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'tags' => $tags,
            'manufacturer' => ['id' => '98432def39fc4624b33213a56b8c944f', 'name' => 'test'],
            'tax' => ['id' => '98432def39fc4624b33213a56b8c944f', 'name' => 'test', 'taxRate' => 15],
        ];
        $context = Context::createDefaultContext();

        $this->repository->create([$data], $context);
    }
}
