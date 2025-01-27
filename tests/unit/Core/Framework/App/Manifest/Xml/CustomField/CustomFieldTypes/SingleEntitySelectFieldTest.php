<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\SingleEntitySelectField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SingleEntitySelectField::class)]
class SingleEntitySelectFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/single-entity-select-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $singleEntitySelectField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(SingleEntitySelectField::class, $singleEntitySelectField);
        static::assertEquals('test_single_entity_select_field', $singleEntitySelectField->getName());
        static::assertEquals([
            'zh-CN' => 'Test single-entity-select field',
        ], $singleEntitySelectField->getLabel());
        static::assertEquals([], $singleEntitySelectField->getHelpText());
        static::assertEquals(1, $singleEntitySelectField->getPosition());
        static::assertEquals(['zh-CN' => 'Choose an entity...'], $singleEntitySelectField->getPlaceholder());
        static::assertFalse($singleEntitySelectField->getRequired());
        static::assertEquals('product', $singleEntitySelectField->getEntity());
    }
}
