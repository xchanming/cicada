<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Framework\Search;

use Cicada\Administration\Framework\Search\CriteriaCollection;
use Cicada\Administration\Notification\NotificationEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CriteriaCollection::class)]
class CriteriaCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new CriteriaCollection();

        $collection->add(new Criteria());

        static::expectExceptionMessage(\sprintf('Expected collection element of type %s got %s', Criteria::class, NotificationEntity::class));
        /** @phpstan-ignore-next-line intentionally wrong parameter provided **/
        $collection->add(new NotificationEntity());

        static::assertCount(1, $collection);
    }
}
