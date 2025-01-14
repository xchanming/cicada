<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Search;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Search\ExtensionCriteria;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExtensionCriteria::class)]
class ExtensionCriteriaTest extends TestCase
{
    public function testFromParameterBagComputesOffset(): void
    {
        $extensionCriteria = ExtensionCriteria::fromArray([
            'limit' => 25,
            'page' => 1,
        ]);

        static::assertEquals(25, $extensionCriteria->getLimit());
        static::assertEquals(0, $extensionCriteria->getOffset());

        $extensionCriteria = ExtensionCriteria::fromArray([
            'limit' => 10,
            'page' => 5,
        ]);

        static::assertEquals(10, $extensionCriteria->getLimit());
        static::assertEquals(40, $extensionCriteria->getOffset());
    }

    public function testItIgnoresInvalidValuesForSortDirection(): void
    {
        $extensionCriteria = new ExtensionCriteria();

        $extensionCriteria->setOrderSequence('random');

        static::assertEquals(ExtensionCriteria::ORDER_SEQUENCE_ASC, $extensionCriteria->getOrderSequence());
    }

    public function testOrderSequenceDesc(): void
    {
        $extensionCriteria = new ExtensionCriteria();

        $extensionCriteria->setOrderSequence('DesC');

        static::assertEquals(ExtensionCriteria::ORDER_SEQUENCE_DESC, $extensionCriteria->getOrderSequence());
    }

    public function testGetQueryOptionsWithMandatory(): void
    {
        $criteria = ExtensionCriteria::fromArray([
            'limit' => 25,
            'page' => 2,
        ]);

        static::assertEquals([
            'limit' => 25,
            'offset' => 25,
        ], $criteria->getQueryParameter());
    }

    public function testGetQueryOptionsWithAllOptions(): void
    {
        $criteria = ExtensionCriteria::fromArray([
            'limit' => 25,
            'page' => 2,
            'sort' => [
                [
                    'field' => 'rating',
                    'order' => 'DESC',
                ],
            ],
            'term' => 'my search',
            'filter' => [[
                'type' => 'multi',
                'operator' => 'AND',
                'queries' => [
                    [
                        'type' => 'equals',
                        'field' => 'rating',
                        'value' => '3',
                    ], [
                        'type' => 'equals',
                        'field' => 'category',
                        'value' => 'living',
                    ],
                ],
            ]],
        ]);

        static::assertEquals([
            'limit' => 25,
            'offset' => 25,
            'orderBy' => 'rating',
            'orderSequence' => 'desc',
            'search' => 'my search',
            'rating' => 3,
            'category' => 'living',
        ], $criteria->getQueryParameter());
    }

    public function testToQueryStringSkipsOrderSequenceIfNoOrderByIsGiven(): void
    {
        $extensionCriteria = new ExtensionCriteria();
        static::assertArrayNotHasKey('orderSequence', $extensionCriteria->getQueryParameter());
        static::assertArrayNotHasKey('orderBy', $extensionCriteria->getQueryParameter());

        $extensionCriteria->setOrderBy('rating');

        static::assertEquals('rating', $extensionCriteria->getQueryParameter()['orderBy']);
        static::assertEquals('rating', $extensionCriteria->getOrderBy());
        static::assertEquals(
            ExtensionCriteria::ORDER_SEQUENCE_ASC,
            $extensionCriteria->getQueryParameter()['orderSequence']
        );
    }

    public function testToQueryStringSkipsSearchIfNotPresent(): void
    {
        $extensionCriteria = new ExtensionCriteria();
        static::assertArrayNotHasKey('search', $extensionCriteria->getQueryParameter());

        $extensionCriteria->setSearch('my search');
        static::assertEquals('my search', $extensionCriteria->getQueryParameter()['search']);
        static::assertEquals('my search', $extensionCriteria->getSearch());
    }

    public function testItFlattensFilterInQuery(): void
    {
        $extensionCriteria = new ExtensionCriteria();
        $extensionCriteria->addFilter([
            'type' => 'multi',
            'operator' => 'AND',
            'queries' => [
                [
                    'type' => 'equals',
                    'field' => 'category',
                    'value' => 'living',
                ], [
                    'type' => 'equals',
                    'field' => 'rating',
                    'value' => '3',
                ],
            ],
        ]);

        static::assertEquals('living', $extensionCriteria->getQueryParameter()['category']);
        static::assertEquals('3', $extensionCriteria->getQueryParameter()['rating']);
    }
}
