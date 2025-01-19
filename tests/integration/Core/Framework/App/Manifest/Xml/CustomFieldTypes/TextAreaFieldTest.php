<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TextAreaFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $textAreaField = $this->importCustomField(__DIR__ . '/_fixtures/text-area-field.xml');

        static::assertSame('test_text_area_field', $textAreaField->getName());
        static::assertSame('html', $textAreaField->getType());
        static::assertTrue($textAreaField->isActive());
        static::assertEquals([
            'label' => [
                'zh-CN' => 'Test text-area field',
            ],
            'helpText' => [],
            'placeholder' => [
                'zh-CN' => 'Enter a text...',
            ],
            'componentName' => 'sw-text-editor',
            'customFieldType' => 'textEditor',
            'customFieldPosition' => 1,
        ], $textAreaField->getConfig());
    }
}
