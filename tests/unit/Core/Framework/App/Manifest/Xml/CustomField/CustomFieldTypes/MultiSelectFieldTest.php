<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\MultiSelectField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MultiSelectField::class)]
class MultiSelectFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/multi-select-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $multiSelectField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(MultiSelectField::class, $multiSelectField);
        static::assertEquals('test_multi_select_field', $multiSelectField->getName());
        static::assertEquals([
            'zh-CN' => 'Test multi-select field',
        ], $multiSelectField->getLabel());
        static::assertEquals([], $multiSelectField->getHelpText());
        static::assertEquals(1, $multiSelectField->getPosition());
        static::assertEquals(['zh-CN' => 'Choose your options...'], $multiSelectField->getPlaceholder());
        static::assertFalse($multiSelectField->getRequired());
        static::assertEquals([
            'first' => [
                'zh-CN' => 'First',
                'en-GB' => 'Erster',
            ],
            'second' => [
                'zh-CN' => 'Second',
            ],
        ], $multiSelectField->getOptions());
    }
}
