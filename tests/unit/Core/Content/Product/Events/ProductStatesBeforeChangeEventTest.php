<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Events;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\UpdatedStates;
use Cicada\Core\Content\Product\Events\ProductStatesBeforeChangeEvent;
use Cicada\Core\Framework\Context;

/**
 * @internal
 */
#[CoversClass(ProductStatesBeforeChangeEvent::class)]
class ProductStatesBeforeChangeEventTest extends TestCase
{
    public function testProductStatesBeforeChangeEvent(): void
    {
        $updatedStates = [new UpdatedStates('foobar', ['foo'], ['bar'])];
        $context = Context::createDefaultContext();

        $event = new ProductStatesBeforeChangeEvent($updatedStates, $context);

        static::assertEquals($updatedStates, $event->getUpdatedStates());
        static::assertEquals($context, $event->getContext());

        $updatedStates = [new UpdatedStates('foobar', ['foo'], ['baz'])];
        $event->setUpdatedStates($updatedStates);

        static::assertEquals($updatedStates, $event->getUpdatedStates());
    }
}
