<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ColorPickerFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $colorPickerField = $this->importCustomField(__DIR__ . '/_fixtures/color-picker-field.xml');

        static::assertSame('test_color_picker_field', $colorPickerField->getName());
        static::assertSame('text', $colorPickerField->getType());
        static::assertTrue($colorPickerField->isActive());
        static::assertEquals([
            'type' => 'colorpicker',
            'label' => [
                'zh-CN' => 'Test color-picker field',
            ],
            'helpText' => [],
            'componentName' => 'sw-field',
            'customFieldType' => 'colorpicker',
            'customFieldPosition' => 1,
        ], $colorPickerField->getConfig());
    }
}
