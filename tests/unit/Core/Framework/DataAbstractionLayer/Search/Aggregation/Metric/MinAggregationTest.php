<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MinAggregation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinAggregation::class)]
class MinAggregationTest extends TestCase
{
    public function testEncode(): void
    {
        $aggregation = new MinAggregation('foo', 'bar');

        static::assertEquals([
            'name' => 'foo',
            'extensions' => [],
            'field' => 'bar',
            '_class' => MinAggregation::class,
        ], $aggregation->jsonSerialize());
    }

    public function testClone(): void
    {
        $aggregation = new MinAggregation('foo', 'bar');
        $clone = clone $aggregation;

        static::assertEquals('foo', $clone->getName());
        static::assertEquals('bar', $clone->getField());
        static::assertEquals($aggregation->jsonSerialize(), $clone->jsonSerialize());
    }
}
