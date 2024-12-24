<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use Cicada\Elasticsearch\Framework\ElasticsearchOutdatedIndexDetector;
use Cicada\Elasticsearch\Framework\ElasticsearchRegistry;
use Cicada\Elasticsearch\Product\ElasticsearchProductDefinition;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElasticsearchOutdatedIndexDetector::class)]
class ElasticsearchOutdatedIndexDetectorTest extends TestCase
{
    public function testUsesChunks(): void
    {
        $indices = $this->createMock(IndicesNamespace::class);
        $indices
            ->expects(static::exactly(2))
            ->method('get')
            ->willReturnCallback(fn () => [
                Uuid::randomHex() => [
                    'aliases' => [
                        'test',
                    ],
                    'settings' => [
                        'index' => [
                            'provided_name' => Uuid::randomHex(),
                        ],
                    ],
                ],
                Uuid::randomHex() => [
                    'aliases' => [],
                    'settings' => [
                        'index' => [
                            'provided_name' => Uuid::randomHex(),
                        ],
                    ],
                ],
            ]);

        $client = $this->createMock(Client::class);
        $client->method('indices')->willReturn($indices);

        $definition = $this->createMock(ElasticsearchProductDefinition::class);

        $registry = $this->createMock(ElasticsearchRegistry::class);
        $registry->method('getDefinitions')->willReturn([$definition, $definition]);

        $makeLanguage = fn () => (new LanguageEntity())->assign(['id' => Uuid::randomHex()]);

        $collection = new EntitySearchResult('test', 1, new LanguageCollection([$makeLanguage(), $makeLanguage(), $makeLanguage()]), null, new Criteria(), Context::createDefaultContext());

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('search')
            ->willReturn($collection);

        $esHelper = $this->createMock(ElasticsearchHelper::class);

        $detector = new ElasticsearchOutdatedIndexDetector($client, $registry, $esHelper);
        $arr = $detector->get();
        static::assertNotNull($arr);
        static::assertCount(1, $arr);
        static::assertCount(2, $detector->getAllUsedIndices());
    }

    public function testDoesNothingWithoutIndices(): void
    {
        $indices = $this->createMock(IndicesNamespace::class);
        $indices
            ->expects(static::exactly(0))
            ->method('get')
            ->willReturnCallback(fn () => []);

        $client = $this->createMock(Client::class);
        $client->method('indices')->willReturn($indices);

        $registry = $this->createMock(ElasticsearchRegistry::class);

        $esHelper = $this->createMock(ElasticsearchHelper::class);

        $detector = new ElasticsearchOutdatedIndexDetector($client, $registry, $esHelper);
        static::assertEmpty($detector->get());
    }
}
