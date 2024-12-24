<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction\Xml;

use Cicada\Core\Framework\App\Flow\Action\Action;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ConfigTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowActionsFile = '/../_fixtures/valid/major/flow.xml';
        $flowActions = Action::createFromXmlFile(__DIR__ . $flowActionsFile);
        static::assertNotNull($flowActions->getActions());

        static::assertCount(1, $flowActions->getActions()->getActions());
        static::assertCount(4, $flowActions->getActions()->getActions()[0]->getConfig()->getConfig());
    }
}
