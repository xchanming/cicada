<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\DataResolver;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\Exception\DuplicateCriteriaKeyException;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(CriteriaCollection::class)]
class CriteriaCollectionTest extends TestCase
{
    public function testAddSingleCriteria(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());

        // test array return
        static::assertCount(1, $collection->all());

        // test iterator
        static::assertCount(1, iterator_to_array($collection));
    }

    public function testAddMultipleCriteriaOfDifferentDefinition(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('key2', MediaDefinition::class, new Criteria());
        $collection->add('key3', CategoryDefinition::class, new Criteria());

        // test array return
        static::assertCount(3, $collection->all());

        // test iterator
        static::assertCount(3, iterator_to_array($collection));
    }

    public function testAddMultipleCriteriaOfSameDefinition(): void
    {
        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('key2', ProductDefinition::class, new Criteria());
        $collection->add('key3', ProductDefinition::class, new Criteria());

        // test array return
        static::assertCount(1, $collection->all());

        // test iterator
        static::assertCount(1, iterator_to_array($collection));

        // test indexed by definition
        static::assertCount(3, $collection->all()[ProductDefinition::class]);
    }

    public function testAddDuplicates(): void
    {
        $this->expectException(DuplicateCriteriaKeyException::class);
        $this->expectExceptionMessage('The key "dup_key" is duplicated in the criteria collection.');

        $collection = new CriteriaCollection();
        $collection->add('key1', ProductDefinition::class, new Criteria());
        $collection->add('dup_key', ProductDefinition::class, new Criteria());
        $collection->add('dup_key', ProductDefinition::class, new Criteria());
    }
}
