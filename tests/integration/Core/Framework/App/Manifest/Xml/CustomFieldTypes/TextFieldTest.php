<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TextFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $textField = $this->importCustomField(__DIR__ . '/_fixtures/text-field.xml');

        static::assertSame('test_text_field', $textField->getName());
        static::assertSame('text', $textField->getType());
        static::assertTrue($textField->isActive());
        static::assertEquals([
            'type' => 'text',
            'label' => [
                'zh-CN' => 'Test text field',
            ],
            'helpText' => [],
            'placeholder' => [
                'zh-CN' => 'Enter a text...',
            ],
            'componentName' => 'sw-field',
            'customFieldType' => 'text',
            'customFieldPosition' => 1,
        ], $textField->getConfig());
    }
}
