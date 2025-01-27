<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\DateTimeField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DateTimeField::class)]
class DateTimeFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/date-time-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $dateTimeField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(DateTimeField::class, $dateTimeField);
        static::assertEquals('test_datetime_field', $dateTimeField->getName());
        static::assertEquals([
            'zh-CN' => 'Test datetime field',
        ], $dateTimeField->getLabel());
        static::assertEquals([], $dateTimeField->getHelpText());
        static::assertEquals(1, $dateTimeField->getPosition());
        static::assertFalse($dateTimeField->getRequired());
    }
}
