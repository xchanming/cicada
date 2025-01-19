<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\FloatField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FloatField::class)]
class FloatFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/float-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $floatField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(FloatField::class, $floatField);
        static::assertEquals('test_float_field', $floatField->getName());
        static::assertEquals([
            'zh-CN' => 'Test float field',
            'en-GB' => 'Test Kommazahlenfeld',
        ], $floatField->getLabel());
        static::assertEquals(['zh-CN' => 'This is a float field.'], $floatField->getHelpText());
        static::assertEquals(2, $floatField->getPosition());
        static::assertEquals(2.2, $floatField->getSteps());
        static::assertEquals(0.5, $floatField->getMin());
        static::assertEquals(1.6, $floatField->getMax());
        static::assertEquals(['zh-CN' => 'Enter a float...'], $floatField->getPlaceholder());
        static::assertFalse($floatField->getRequired());
    }
}
