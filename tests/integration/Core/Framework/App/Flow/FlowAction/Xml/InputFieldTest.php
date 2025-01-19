<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction\Xml;

use Cicada\Core\Framework\App\Flow\Action\Action;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class InputFieldTest extends TestCase
{
    public function testFromXml(): void
    {
        $flowActionsFile = '/../_fixtures/valid/major/flow.xml';
        $flowActions = Action::createFromXmlFile(__DIR__ . $flowActionsFile);
        static::assertNotNull($flowActions->getActions());
        static::assertCount(1, $flowActions->getActions()->getActions());
        $config = $flowActions->getActions()->getActions()[0]->getConfig()->getConfig();
        static::assertCount(4, $config);

        $firstInputField = $config[0];

        static::assertSame('textField', $firstInputField->getName());
        static::assertSame('text', $firstInputField->getType());
        static::assertEquals([
            'zh-CN' => 'To',
            'en-GB' => 'To DE',
        ], $firstInputField->getLabel());
        static::assertEquals([
            'zh-CN' => 'Enter to...',
            'en-GB' => 'Enter to DE...',
        ], $firstInputField->getPlaceHolder());
        static::assertEquals([
            'zh-CN' => 'Help text',
            'en-GB' => 'Help text DE',
        ], $firstInputField->getHelpText());

        static::assertTrue($firstInputField->getRequired());
        static::assertSame('Cicada 6', $firstInputField->getDefaultValue());
    }
}
