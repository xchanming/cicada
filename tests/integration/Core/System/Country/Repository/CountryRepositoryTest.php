<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Country\Repository;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\EntityScoreQueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchTermInterpreter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@discovery')]
class CountryRepositoryTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<CountryCollection>
     */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('country.repository');
    }

    public function testSearchRanking(): void
    {
        $recordA = Uuid::randomHex();
        $recordB = Uuid::randomHex();

        $records = [
            ['id' => $recordA, 'name' => 'match'],
            ['id' => $recordB, 'name' => 'not', 'iso' => 'match'],
        ];

        $this->repository->create($records, Context::createDefaultContext());

        $criteria = new Criteria();

        $context = Context::createDefaultContext();
        $builder = static::getContainer()->get(EntityScoreQueryBuilder::class);
        $pattern = static::getContainer()->get(SearchTermInterpreter::class)->interpret('match');
        $queries = $builder->buildScoreQueries(
            $pattern,
            $this->repository->getDefinition(),
            $this->repository->getDefinition()->getEntityName(),
            $context
        );
        $criteria->addQuery(...$queries);

        $result = $this->repository->searchIds($criteria, Context::createDefaultContext());

        static::assertCount(2, $result->getIds());

        static::assertEquals(
            [$recordA, $recordB],
            $result->getIds()
        );

        static::assertGreaterThan(
            $result->getDataFieldOfId($recordB, '_score'),
            $result->getDataFieldOfId($recordA, '_score')
        );
    }
}
