<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction\Xml;

use Cicada\Core\Framework\App\Flow\Action\Action;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ActionTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowActionsFile = '/../_fixtures/valid/major/flow.xml';
        $flowActions = Action::createFromXmlFile(__DIR__ . $flowActionsFile);

        static::assertNotNull($flowActions->getActions());
        static::assertCount(1, $flowActions->getActions()->getActions());

        $firstAction = $flowActions->getActions()->getActions()[0];

        static::assertSame('abc.cde.ccc', $firstAction->getMeta()->getName());
        static::assertSame(['order', 'customer'], $firstAction->getMeta()->getRequirements());
        static::assertSame(
            [
                'zh-CN' => 'First action app',
                'en-GB' => 'First action app DE',
            ],
            $firstAction->getMeta()->getLabel()
        );

        static::assertSame('string', $firstAction->getHeaders()->getParameters()[0]->getType());
        static::assertSame('string', $firstAction->getParameters()->getParameters()[0]->getType());
        static::assertSame('text', $firstAction->getConfig()->getConfig()[0]->getType());
    }
}
