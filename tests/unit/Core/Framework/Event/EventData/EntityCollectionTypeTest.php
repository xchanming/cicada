<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Event\EventData;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Framework\Event\EventData\EntityCollectionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityCollectionType::class)]
class EntityCollectionTypeTest extends TestCase
{
    public function testToArray(): void
    {
        $expected = [
            'type' => 'collection',
            'entityClass' => CustomerDefinition::class,
        ];

        static::assertEquals($expected, (new EntityCollectionType(CustomerDefinition::class))->toArray());
    }
}
