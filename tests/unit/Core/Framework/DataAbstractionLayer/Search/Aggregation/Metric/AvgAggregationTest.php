<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AvgAggregation::class)]
class AvgAggregationTest extends TestCase
{
    public function testEncode(): void
    {
        $aggregation = new AvgAggregation('foo', 'bar');

        static::assertEquals([
            'name' => 'foo',
            'extensions' => [],
            'field' => 'bar',
            '_class' => AvgAggregation::class,
        ], $aggregation->jsonSerialize());
    }

    public function testClone(): void
    {
        $aggregation = new AvgAggregation('foo', 'bar');
        $clone = clone $aggregation;

        static::assertEquals('foo', $clone->getName());
        static::assertEquals('bar', $clone->getField());
        static::assertEquals($aggregation->jsonSerialize(), $clone->jsonSerialize());
    }
}
