<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Action\Xml;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Flow\Action\Xml\Actions;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @internal
 */
#[CoversClass(Actions::class)]
class ActionsTest extends TestCase
{
    public function testFromXml(): void
    {
        $document = XmlUtils::loadFile(
            __DIR__ . '/../../../_fixtures/Resources/flow.xml',
            __DIR__ . '/../../../../../../../../src/Core/Framework/App/Flow/Schema/flow-1.0.xsd'
        );

        $actions = $document->getElementsByTagName('flow-actions')->item(0);
        static::assertNotNull($actions);

        $action = Actions::fromXml($actions);
        static::assertCount(1, $action->getActions());
        static::assertSame('abc.cde.ccc', $action->getActions()[0]->getMeta()->getName());
    }
}
