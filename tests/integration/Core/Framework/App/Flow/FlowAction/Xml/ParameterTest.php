<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction\Xml;

use Cicada\Core\Framework\App\Flow\Action\Action;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ParameterTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowActionsFile = '/../_fixtures/valid/major/flow.xml';
        $flowActions = Action::createFromXmlFile(__DIR__ . $flowActionsFile);

        static::assertNotNull($flowActions->getActions());
        static::assertCount(1, $flowActions->getActions()->getActions());

        $firstAction = $flowActions->getActions()->getActions()[0];
        $firstHeaderParameter = $firstAction->getHeaders()->getParameters()[0];
        $firstParameter = $firstAction->getParameters()->getParameters()[0];

        static::assertSame('string', $firstHeaderParameter->getType());
        static::assertSame('content-type', $firstHeaderParameter->getName());
        static::assertSame('application/json', $firstHeaderParameter->getValue());

        static::assertSame('string', $firstParameter->getType());
        static::assertSame('to', $firstParameter->getName());
        static::assertSame('{{ customer.name }}', $firstParameter->getValue());
    }
}
