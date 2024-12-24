<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing;

use Cicada\Core\Framework\Context;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use Cicada\Elasticsearch\Framework\ElasticsearchRegistry;
use Cicada\Elasticsearch\Framework\Indexing\IndexMappingProvider;
use Cicada\Elasticsearch\Framework\Indexing\IndexMappingUpdater;
use Cicada\Elasticsearch\Product\ElasticsearchProductDefinition;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IndexMappingUpdater::class)]
class IndexMappingUpdaterTest extends TestCase
{
    public function testUpdate(): void
    {
        $elasticsearchHelper = $this->createMock(ElasticsearchHelper::class);
        $elasticsearchHelper->method('getIndexName')->willReturn('index');

        $registry = new ElasticsearchRegistry([
            $this->createMock(ElasticsearchProductDefinition::class),
        ]);

        $client = $this->createMock(Client::class);
        $indicesNamespace = $this->createMock(IndicesNamespace::class);
        $indicesNamespace
            ->expects(static::once())
            ->method('putMapping')
            ->with([
                'index' => 'index',
                'body' => [
                    'foo' => '1',
                ],
            ]);

        $client
            ->method('indices')
            ->willReturn($indicesNamespace);

        $indexMappingProvider = $this->createMock(IndexMappingProvider::class);
        $indexMappingProvider
            ->method('build')
            ->willReturn(['foo' => '1']);

        $updater = new IndexMappingUpdater(
            $registry,
            $elasticsearchHelper,
            $client,
            $indexMappingProvider
        );

        $updater->update(Context::createDefaultContext());
    }
}
