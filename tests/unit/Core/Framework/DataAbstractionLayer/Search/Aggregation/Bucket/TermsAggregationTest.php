<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TermsAggregation::class)]
class TermsAggregationTest extends TestCase
{
    public function testEncode(): void
    {
        $aggregation = new TermsAggregation('foo', 'name');
        static::assertEquals([
            'name' => 'foo',
            'extensions' => [],
            'field' => 'name',
            'aggregation' => null,
            'limit' => null,
            'sorting' => null,
            '_class' => TermsAggregation::class,
        ], $aggregation->jsonSerialize());
    }

    public function testClone(): void
    {
        $aggregation = new TermsAggregation('foo', 'name');
        $clone = clone $aggregation;
        static::assertEquals($aggregation->getName(), $clone->getName());
        static::assertEquals($aggregation->getFields(), $clone->getFields());
        static::assertEquals($aggregation->jsonSerialize(), $clone->jsonSerialize());
    }
}
