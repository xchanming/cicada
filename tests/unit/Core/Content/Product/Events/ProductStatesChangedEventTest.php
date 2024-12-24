<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Events;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\UpdatedStates;
use Cicada\Core\Content\Product\Events\ProductStatesChangedEvent;
use Cicada\Core\Framework\Context;

/**
 * @internal
 */
#[CoversClass(ProductStatesChangedEvent::class)]
class ProductStatesChangedEventTest extends TestCase
{
    public function testProductStatesChangedEvent(): void
    {
        $updatedStates = [new UpdatedStates('foobar', ['foo'], ['bar'])];
        $context = Context::createDefaultContext();

        $event = new ProductStatesChangedEvent($updatedStates, $context);

        static::assertEquals($updatedStates, $event->getUpdatedStates());
        static::assertEquals($context, $event->getContext());
    }
}
