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
            'en-GB' => 'Test multi-select field',
        ], $multiSelectField->getLabel());
        static::assertEquals([], $multiSelectField->getHelpText());
        static::assertEquals(1, $multiSelectField->getPosition());
        static::assertEquals(['en-GB' => 'Choose your options...'], $multiSelectField->getPlaceholder());
        static::assertFalse($multiSelectField->getRequired());
        static::assertEquals([
            'first' => [
                'en-GB' => 'First',
                'zh-CN' => 'Erster',
            ],
            'second' => [
                'en-GB' => 'Second',
            ],
        ], $multiSelectField->getOptions());
    }
}
