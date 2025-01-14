<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Elasticsearch\Framework\ElasticsearchRegistry;
use Cicada\Elasticsearch\Product\ElasticsearchProductDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElasticsearchRegistry::class)]
class ElasticsearchRegistryTest extends TestCase
{
    public function testRegistry(): void
    {
        $definition = $this->createMock(ElasticsearchProductDefinition::class);
        $definition
            ->method('getEntityDefinition')
            ->willReturn(new ProductDefinition());

        $registry = new ElasticsearchRegistry([
            $definition,
        ]);

        static::assertTrue($registry->has('product'));
        static::assertInstanceOf(ElasticsearchProductDefinition::class, $registry->get('product'));

        static::assertFalse($registry->has('category'));
        static::assertNull($registry->get('category'));

        static::assertEquals(['product'], $registry->getDefinitionNames());
    }
}
