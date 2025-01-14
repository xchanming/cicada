<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Action\Xml;

use Cicada\Core\Framework\App\Flow\Action\Xml\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * @internal
 */
#[CoversClass(Parameters::class)]
class ParametersTest extends TestCase
{
    public function testFromXml(): void
    {
        $document = XmlUtils::loadFile(
            __DIR__ . '/../../../_fixtures/Resources/flow.xml',
            __DIR__ . '/../../../../../../../../src/Core/Framework/App/Flow/Schema/flow-1.0.xsd'
        );
        $actions = $document->getElementsByTagName('flow-actions')->item(0);
        static::assertNotNull($actions);
        $action = $actions->getElementsByTagName('flow-action')->item(0);
        static::assertNotNull($action);
        $parameters = $action->getElementsByTagName('parameters')->item(0);
        static::assertNotNull($parameters);

        $result = Parameters::fromXml($parameters)->getParameters();
        static::assertCount(3, $result);
        static::assertSame('string', $result[0]->getType());
        static::assertSame('string', $result[1]->getType());
        static::assertSame('string', $result[2]->getType());
    }
}
