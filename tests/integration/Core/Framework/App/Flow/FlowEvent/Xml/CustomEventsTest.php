<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowEvent\Xml;

use Cicada\Core\Framework\App\Flow\Event\Event;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CustomEventsTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowEvents = Event::createFromXmlFile(__DIR__ . '/../_fixtures/valid/flowEventWithFlowEvents.xml');
        static::assertNotNull($flowEvents->getCustomEvents());
        static::assertCount(1, $flowEvents->getCustomEvents()->getCustomEvents());
    }
}
