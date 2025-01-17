<?php
declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\DataResolver\Element;

use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(ElementDataCollection::class)]
class ElementDataCollectionTest extends TestCase
{
    public function testItIterates(): void
    {
        $collection = new ElementDataCollection();
        $collection->add('a', new EntitySearchResult(
            'product',
            0,
            new ProductCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        ));

        static::assertCount(1, $collection);
        static::assertContainsOnlyInstancesOf(EntitySearchResult::class, $collection);
    }
}
