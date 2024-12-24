<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\BucketAggregation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BucketAggregation::class)]
class BucketAggregationTest extends TestCase
{
    public function testEncode(): void
    {
        $aggregation = new BucketAggregation('test', 'test', null);

        static::assertSame([
            'extensions' => [],
            'name' => 'test',
            'field' => 'test',
            'aggregation' => null,
            '_class' => BucketAggregation::class,
        ], $aggregation->jsonSerialize());
    }

    public function testClone(): void
    {
        $aggregation = new BucketAggregation('test', 'test', null);
        $clone = clone $aggregation;

        static::assertEquals($aggregation->getField(), $clone->getField());
        static::assertEquals($aggregation->jsonSerialize(), $clone->jsonSerialize());
        static::assertNotSame($aggregation, $clone);
    }
}
