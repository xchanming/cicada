<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\TextField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TextField::class)]
class TextFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/text-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $textField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(TextField::class, $textField);
        static::assertEquals('test_text_field', $textField->getName());
        static::assertEquals([
            'zh-CN' => 'Test text field',
        ], $textField->getLabel());
        static::assertEquals([], $textField->getHelpText());
        static::assertEquals(1, $textField->getPosition());
        static::assertEquals(['zh-CN' => 'Enter a text...'], $textField->getPlaceholder());
        static::assertFalse($textField->getRequired());
    }
}
