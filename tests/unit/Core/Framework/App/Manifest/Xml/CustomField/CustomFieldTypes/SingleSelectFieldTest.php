<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\SingleSelectField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SingleSelectField::class)]
class SingleSelectFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/single-select-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $singleSelectField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(SingleSelectField::class, $singleSelectField);
        static::assertEquals('test_single_select_field', $singleSelectField->getName());
        static::assertEquals([
            'zh-CN' => 'Test single-select field',
        ], $singleSelectField->getLabel());
        static::assertEquals([], $singleSelectField->getHelpText());
        static::assertEquals(1, $singleSelectField->getPosition());
        static::assertEquals(['zh-CN' => 'Choose an option...'], $singleSelectField->getPlaceholder());
        static::assertFalse($singleSelectField->getRequired());
        static::assertEquals([
            'first' => [
                'zh-CN' => 'First',
                'en-GB' => 'Erster',
            ],
            'second' => [
                'zh-CN' => 'Second',
            ],
        ], $singleSelectField->getOptions());
    }
}
