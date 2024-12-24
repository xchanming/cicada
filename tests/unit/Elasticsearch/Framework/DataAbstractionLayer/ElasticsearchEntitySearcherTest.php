<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\DataAbstractionLayer;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\System\CustomField\CustomFieldService;
use Cicada\Elasticsearch\ElasticsearchException;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\AbstractElasticsearchSearchHydrator;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\CriteriaParser;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\ElasticsearchEntitySearcher;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\Event\ElasticsearchEntitySearcherSearchedEvent;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\Event\ElasticsearchEntitySearcherSearchEvent;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use OpenSearch\Client;
use OpenSearch\Common\Exceptions\NoNodesAvailableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(ElasticsearchEntitySearcher::class)]
class ElasticsearchEntitySearcherTest extends TestCase
{
    public function testEmptyQueryExceptionIsCatched(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);

        $client = $this->createMock(Client::class);
        // client should not be used if limit is 0
        $client->expects(static::never())
            ->method('search');

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);
        $helper
            ->method('addTerm')
            ->willThrowException(ElasticsearchException::emptyQuery());

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $result = $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertEquals(0, $result->getTotal());
    }

    public function testWithCriteriaLimitOfZero(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(0);

        $client = $this->createMock(Client::class);
        // client should not be used if limit is 0
        $client->expects(static::never())
            ->method('search');

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '5s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $result = $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertEquals(0, $result->getTotal());
    }

    public function testSearchWithCount(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => true,
                'body' => [
                    'timeout' => '10s',
                    'from' => 0,
                    'size' => 10,
                ],
                'search_type' => 'dfs_query_then_fetch',
            ])->willReturn([]);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );
    }

    public function testSearchWithNoCount(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => false,
                'body' => [
                    'timeout' => '10s',
                    'from' => 0,
                    'size' => 10,
                ],
                'search_type' => 'dfs_query_then_fetch',
            ])->willReturn([]);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );
    }

    public function testSearchWithExplainMode(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => false,
                'include_named_queries_score' => true,
                'body' => [
                    'timeout' => '10s',
                    'from' => 0,
                    'size' => 10,
                    'explain' => true,
                ],
                'search_type' => 'dfs_query_then_fetch',
            ])->willReturn([]);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();
        $context->addState(ElasticsearchEntitySearcher::EXPLAIN_MODE);

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );
    }

    public function testDispatchEvents(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $context = Context::createDefaultContext();

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => false,
                'body' => [
                    'timeout' => '10s',
                    'from' => 0,
                    'size' => 10,
                ],
                'search_type' => 'dfs_query_then_fetch',
            ])->willReturn([
                'hits' => [
                    'hits' => [],
                ],
            ]);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $dispatcher = new EventDispatcher();
        $searchEventDispatched = false;
        $searchedEventDispatched = false;

        $dispatcher->addListener(ElasticsearchEntitySearcherSearchEvent::class, static function (ElasticsearchEntitySearcherSearchEvent $event) use (&$searchEventDispatched): void {
            $searchEventDispatched = true;
        });

        $dispatcher->addListener(ElasticsearchEntitySearcherSearchedEvent::class, static function (ElasticsearchEntitySearcherSearchedEvent $event) use (&$searchedEventDispatched): void {
            $searchedEventDispatched = true;
            static::assertEquals([
                'hits' => [
                    'hits' => [],
                ],
            ], $event->result);
        });

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            $this->createMock(CriteriaParser::class),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            $dispatcher,
            '10s',
            'dfs_query_then_fetch'
        );

        $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertTrue($searchEventDispatched);
        static::assertTrue($searchedEventDispatched);
    }

    public function testExceptionsGetLogged(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $client = $this->createMock(Client::class);
        // client should not be used if limit is 0
        $client->expects(static::once())
            ->method('search')
            ->willThrowException(new NoNodesAvailableException());

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper->expects(static::once())->method('logAndThrowException');
        $helper->method('allowSearch')->willReturn(true);

        $searcher = new ElasticsearchEntitySearcher(
            $client,
            $this->createMock(EntitySearcherInterface::class),
            $helper,
            new CriteriaParser(new EntityDefinitionQueryHelper(), $this->createMock(CustomFieldService::class)),
            $this->createMock(AbstractElasticsearchSearchHydrator::class),
            new EventDispatcher(),
            '5s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();
        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $result = $searcher->search(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertEquals(0, $result->getTotal());
    }
}
