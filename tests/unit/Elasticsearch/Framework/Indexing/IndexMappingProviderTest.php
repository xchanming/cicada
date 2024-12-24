<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Cicada\Elasticsearch\Framework\Indexing\IndexMappingProvider;

/**
 * @internal
 */
#[CoversClass(IndexMappingProvider::class)]
class IndexMappingProviderTest extends TestCase
{
    public function testBuild(): void
    {
        $mapping = [
            'foo' => 'bar',
        ];

        $definition = $this->createMock(AbstractElasticsearchDefinition::class);
        $definition->method('getMapping')->willReturn([
            'bar' => 'foo',
        ]);

        $provider = new IndexMappingProvider($mapping);

        static::assertEquals(
            [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
            $provider->build(
                $definition,
                Context::createDefaultContext()
            )
        );
    }
}
