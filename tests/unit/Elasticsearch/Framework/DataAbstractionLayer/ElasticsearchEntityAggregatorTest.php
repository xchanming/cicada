<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\DataAbstractionLayer;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Elasticsearch\ElasticsearchException;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\AbstractElasticsearchAggregationHydrator;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\ElasticsearchEntityAggregator;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\Event\ElasticsearchEntityAggregatorSearchedEvent;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\Event\ElasticsearchEntityAggregatorSearchEvent;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use OpenSearch\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(ElasticsearchEntityAggregator::class)]
class ElasticsearchEntityAggregatorTest extends TestCase
{
    public function testNoAggregations(): void
    {
        $criteria = new Criteria();

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

        $searcher = new ElasticsearchEntityAggregator(
            $helper,
            $client,
            $this->createMock(EntityAggregatorInterface::class),
            $this->createMock(AbstractElasticsearchAggregationHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $result = $searcher->aggregate(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertCount(0, $result->getElements());
    }

    public function testEmptyQueryExceptionIsCatched(): void
    {
        $criteria = new Criteria();

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

        $searcher = new ElasticsearchEntityAggregator(
            $helper,
            $client,
            $this->createMock(EntityAggregatorInterface::class),
            $this->createMock(AbstractElasticsearchAggregationHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $result = $searcher->aggregate(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertCount(0, $result->getElements());
    }

    public function testAggregateWithTimeout(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->addAggregation(new TermsAggregation('test', 'test'));

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => false,
                'body' => [
                    'timeout' => '10s',
                    'size' => 0,
                ],
                'search_type' => 'dfs_query_then_fetch',
            ])->willReturn([]);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper
            ->method('allowSearch')
            ->willReturn(true);

        $searcher = new ElasticsearchEntityAggregator(
            $helper,
            $client,
            $this->createMock(EntityAggregatorInterface::class),
            $this->createMock(AbstractElasticsearchAggregationHydrator::class),
            new EventDispatcher(),
            '10s',
            'dfs_query_then_fetch'
        );

        $context = Context::createDefaultContext();

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $searcher->aggregate(
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
        $criteria->addAggregation(new TermsAggregation('test', 'test'));

        $context = Context::createDefaultContext();

        $client = $this->createMock(Client::class);

        $client->expects(static::once())
            ->method('search')->with([
                'index' => '',
                'track_total_hits' => false,
                'body' => [
                    'timeout' => '10s',
                    'size' => 0,
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

        $dispatcher->addListener(ElasticsearchEntityAggregatorSearchEvent::class, static function (ElasticsearchEntityAggregatorSearchEvent $event) use (&$searchEventDispatched): void {
            $searchEventDispatched = true;
        });

        $dispatcher->addListener(ElasticsearchEntityAggregatorSearchedEvent::class, static function (ElasticsearchEntityAggregatorSearchedEvent $event) use (&$searchedEventDispatched): void {
            $searchedEventDispatched = true;
            static::assertEquals([
                'hits' => [
                    'hits' => [],
                ],
            ], $event->result);
        });

        $aggregator = new ElasticsearchEntityAggregator(
            $helper,
            $client,
            $this->createMock(EntityAggregatorInterface::class),
            $this->createMock(AbstractElasticsearchAggregationHydrator::class),
            $dispatcher,
            '10s',
            'dfs_query_then_fetch'
        );

        $aggregator->aggregate(
            new ProductDefinition(),
            $criteria,
            $context
        );

        static::assertTrue($searchEventDispatched);
        static::assertTrue($searchedEventDispatched);
    }
}
