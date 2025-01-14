<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityAggregation::class)]
class EntityAggregationTest extends TestCase
{
    public function testEncode(): void
    {
        $aggregation = new EntityAggregation('foo', 'bar', 'product');

        static::assertEquals([
            'name' => 'foo',
            'extensions' => [],
            'field' => 'bar',
            'entity' => 'product',
            '_class' => EntityAggregation::class,
        ], $aggregation->jsonSerialize());
    }

    public function testClone(): void
    {
        $aggregation = new EntityAggregation('foo', 'bar', 'product');
        $clone = clone $aggregation;

        static::assertEquals('foo', $clone->getName());
        static::assertEquals('bar', $clone->getField());
        static::assertEquals($aggregation->jsonSerialize(), $clone->jsonSerialize());
    }
}
