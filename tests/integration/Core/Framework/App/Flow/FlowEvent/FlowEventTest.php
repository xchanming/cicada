<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowEvent;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Flow\Event\Event;
use Cicada\Core\Framework\Feature;
use Cicada\Core\System\SystemConfig\Exception\XmlParsingException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class FlowEventTest extends TestCase
{
    public function testCreateFromXmlWithFlowEvent(): void
    {
        $flowEventsFile = '/_fixtures/valid/flowEventWithFlowEvents.xml';
        $flowEvents = Event::createFromXmlFile(__DIR__ . $flowEventsFile);

        static::assertSame(__DIR__ . '/_fixtures/valid', $flowEvents->getPath());
        static::assertNotNull($flowEvents->getCustomEvents());
        static::assertCount(1, $flowEvents->getCustomEvents()->getCustomEvents());
    }

    public function testCreateFromXmlMissingFlowEvent(): void
    {
        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(AppException::class);
        } else {
            $this->expectException(XmlParsingException::class);
        }

        $this->expectExceptionMessage('[ERROR 1871] Element \'flow-events\': Missing child element(s). Expected is ( flow-event ).');

        $flowEventsFile = '/_fixtures/invalid/flowEventWithoutFlowEvents.xml';
        Event::createFromXmlFile(__DIR__ . $flowEventsFile);
    }

    public function testCreateFromXmlFlowEventMissingRequiredChild(): void
    {
        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(AppException::class);
        } else {
            $this->expectException(XmlParsingException::class);
        }

        $this->expectExceptionMessage('[ERROR 1871] Element \'flow-event\': Missing child element(s). Expected is ( name ).');

        $flowEventsFile = '/_fixtures/invalid/flowEventWithoutRequiredChild.xml';
        Event::createFromXmlFile(__DIR__ . $flowEventsFile);
    }

    public function testCreateFromXmlFlowEventMetaMissingRequiredChild(): void
    {
        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(AppException::class);
        } else {
            $this->expectException(XmlParsingException::class);
        }

        $this->expectExceptionMessage('Message: [ERROR 1871] Element \'flow-event\': Missing child element(s). Expected is ( aware ).');

        $flowEventsFile = '/_fixtures/invalid/flowEventMetaWithoutRequiredChild.xml';
        Event::createFromXmlFile(__DIR__ . $flowEventsFile);
    }
}
