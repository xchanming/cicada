<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Events;

use Cicada\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(BeforeLoadStorableFlowDataEvent::class)]
class BeforeLoadStorableFlowDataEventTest extends TestCase
{
    public function testGetters(): void
    {
        $event = new BeforeLoadStorableFlowDataEvent(
            'entity_name',
            new Criteria(),
            Context::createDefaultContext()
        );

        static::assertSame('entity_name', $event->getEntityName());
        static::assertSame('flow.storer.entity_name.criteria.event', $event->getName());
    }
}
