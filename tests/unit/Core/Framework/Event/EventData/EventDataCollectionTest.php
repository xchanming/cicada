<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Event\EventData;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EventDataCollection::class)]
class EventDataCollectionTest extends TestCase
{
    public function testToArray(): void
    {
        $collection = (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('myBool', new ScalarValueType(ScalarValueType::TYPE_BOOL))
        ;

        $expected = [
            'customer' => [
                'type' => 'entity',
                'entityClass' => CustomerDefinition::class,
                'entityName' => 'customer',
            ],
            'myBool' => [
                'type' => 'bool',
            ],
        ];

        static::assertEquals($expected, $collection->toArray());
    }
}
