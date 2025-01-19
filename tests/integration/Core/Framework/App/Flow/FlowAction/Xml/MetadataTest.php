<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction\Xml;

use Cicada\Core\Framework\App\Flow\Action\Action;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MetadataTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowActionsFile = '/../_fixtures/valid/major/flow.xml';
        $flowActions = Action::createFromXmlFile(__DIR__ . $flowActionsFile);

        static::assertNotNull($flowActions->getActions());
        static::assertCount(1, $flowActions->getActions()->getActions());

        $firstAction = $flowActions->getActions()->getActions()[0];
        $meta = $firstAction->getMeta();

        static::assertSame('abc.cde.ccc', $meta->getName());
        static::assertSame(['order', 'customer'], $meta->getRequirements());
        static::assertSame('https://example.xyz', $meta->getUrl());
        static::assertSame('sw-pencil', $meta->getSwIcon());
        static::assertSame('resource/pencil', $meta->getIcon());
        static::assertEquals(
            [
                'zh-CN' => 'First action app',
                'en-GB' => 'First action app DE',
            ],
            $firstAction->getMeta()->getLabel()
        );
        static::assertEquals(
            [
                'zh-CN' => 'First action app description',
                'en-GB' => 'First action app description DE',
            ],
            $firstAction->getMeta()->getDescription()
        );
        static::assertEquals(
            [
                'zh-CN' => 'Headline for action',
                'en-GB' => 'Überschrift für Aktion',
            ],
            $firstAction->getMeta()->getHeadline()
        );
    }
}
