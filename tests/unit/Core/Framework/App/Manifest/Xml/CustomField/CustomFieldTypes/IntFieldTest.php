<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\IntField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IntField::class)]
class IntFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/int-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $intField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(IntField::class, $intField);
        static::assertEquals('test_int_field', $intField->getName());
        static::assertEquals([
            'zh-CN' => 'Test int field',
            'en-GB' => 'Test Ganzzahlenfeld',
        ], $intField->getLabel());
        static::assertEquals(['zh-CN' => 'This is an int field.'], $intField->getHelpText());
        static::assertEquals(1, $intField->getPosition());
        static::assertEquals(2, $intField->getSteps());
        static::assertEquals(0, $intField->getMin());
        static::assertEquals(1, $intField->getMax());
        static::assertEquals(['zh-CN' => 'Enter an int...'], $intField->getPlaceholder());
        static::assertTrue($intField->getRequired());
    }
}
