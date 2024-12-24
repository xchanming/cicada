<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Currency\Repository;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\EntityScoreQueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchTermInterpreter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\RestrictDeleteViolationException;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Currency\CurrencyDefinition;

/**
 * @internal
 */
class CurrencyRepositoryTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<CurrencyCollection>
     */
    private EntityRepository $currencyRepository;

    protected function setUp(): void
    {
        $this->currencyRepository = static::getContainer()->get('currency.repository');
    }

    public function testSearchRanking(): void
    {
        $recordA = Uuid::randomHex();
        $recordB = Uuid::randomHex();

        $records = [
            [
                'id' => $recordA,
                'decimalPrecision' => 2,
                'name' => 'match',
                'isoCode' => 'FOO',
                'shortName' => 'test',
                'factor' => 1,
                'symbol' => 'A',
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            ],
            [
                'id' => $recordB,
                'decimalPrecision' => 2,
                'name' => 'not',
                'isoCode' => 'BAR',
                'shortName' => 'match',
                'factor' => 1,
                'symbol' => 'A',
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            ],
        ];

        $this->currencyRepository->create($records, Context::createDefaultContext());

        $criteria = new Criteria();

        $builder = static::getContainer()->get(EntityScoreQueryBuilder::class);
        $pattern = static::getContainer()->get(SearchTermInterpreter::class)->interpret('match');
        $context = Context::createDefaultContext();
        $queries = $builder->buildScoreQueries(
            $pattern,
            $this->currencyRepository->getDefinition(),
            $this->currencyRepository->getDefinition()->getEntityName(),
            $context
        );
        $criteria->addQuery(...$queries);

        $result = $this->currencyRepository->searchIds($criteria, Context::createDefaultContext());

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

    public function testDeleteNonDefaultCurrency(): void
    {
        $context = Context::createDefaultContext();
        $recordA = Uuid::randomHex();

        $records = [
            [
                'id' => $recordA,
                'decimalPrecision' => 2,
                'name' => 'match',
                'isoCode' => 'FOO',
                'shortName' => 'test',
                'factor' => 1,
                'symbol' => 'A',
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            ],
        ];

        $this->currencyRepository->create($records, $context);

        $deleteEventElement = $this->currencyRepository->delete([['id' => $recordA]], $context)->getEventByEntityName(CurrencyDefinition::ENTITY_NAME);

        static::assertNotNull($deleteEventElement);
        static::assertEquals($recordA, $deleteEventElement->getWriteResults()[0]->getPrimaryKey());
    }

    public function testDeleteDefaultCurrency(): void
    {
        $context = Context::createDefaultContext();

        $this->expectException(RestrictDeleteViolationException::class);
        $this->currencyRepository->delete([['id' => Defaults::CURRENCY]], $context);
    }
}
