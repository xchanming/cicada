<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Repository;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\EntityScoreQueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchPattern;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchTerm;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class ProductSearchScoringTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product.repository');
    }

    public function testScoringExtensionExists(): void
    {
        $context = Context::createDefaultContext();
        $pattern = new SearchPattern(new SearchTerm('test'));
        $builder = new EntityScoreQueryBuilder();
        $queries = $builder->buildScoreQueries(
            $pattern,
            static::getContainer()->get(ProductDefinition::class),
            static::getContainer()->get(ProductDefinition::class)->getEntityName(),
            $context
        );

        $criteria = new Criteria();
        $criteria->addQuery(...$queries);

        $this->repository->create([
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 10, 'name' => 'product 1 test', 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test'], 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]]],
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 10, 'name' => 'product 2 test', 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test'], 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]]],
        ], $context);

        foreach ($this->repository->search($criteria, $context)->getEntities() as $entity) {
            static::assertArrayHasKey('search', $entity->getExtensions());
            $extension = $entity->getExtension('search');

            static::assertInstanceOf(ArrayEntity::class, $extension);
            static::assertArrayHasKey('_score', $extension);
            static::assertGreaterThan(0, (float) $extension->get('_score'));
        }
    }
}
