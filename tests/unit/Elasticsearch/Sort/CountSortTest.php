<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Sort;

use OpenSearchDSL\Sort\FieldSort;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Elasticsearch\Sort\CountSort;

/**
 * @internal
 */
#[CoversClass(CountSort::class)]
class CountSortTest extends TestCase
{
    public function testSerialize(): void
    {
        $sort = new CountSort('test.test', FieldSort::ASC);

        static::assertEquals(
            [
                'test._count' => [
                    'nested' => [
                        'path' => 'test',
                    ],
                    'missing' => 0,
                    'order' => 'asc',
                    'mode' => 'sum',
                ],
            ],
            $sort->toArray()
        );
    }
}
