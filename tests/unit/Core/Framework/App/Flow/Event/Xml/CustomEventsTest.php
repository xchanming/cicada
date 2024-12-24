<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Event\Xml;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Flow\Event\Xml\CustomEvents;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @internal
 */
#[CoversClass(CustomEvents::class)]
class CustomEventsTest extends TestCase
{
    public function testFromXml(): void
    {
        $doc = XmlUtils::loadFile(
            __DIR__ . '/../../../_fixtures/Resources/flow.xml',
            __DIR__ . '/../../../../../../../../src/Core/Framework/App/Flow/Schema/flow-1.0.xsd'
        );

        $events = $doc->getElementsByTagName('flow-events')->item(0);
        static::assertNotNull($events);
        $result = CustomEvents::fromXml($events)->toArray('en-GB');
        static::assertArrayHasKey('customEvent', $result);
    }
}
