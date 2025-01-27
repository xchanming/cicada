<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\MediaSelectionField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MediaSelectionField::class)]
class MediaSelectionFieldTest extends TestCase
{
    public function testCreateFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/media-selection-field.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $mediaSelectionField = $customFieldSet->getFields()[0];
        static::assertInstanceOf(MediaSelectionField::class, $mediaSelectionField);
        static::assertEquals('test_media_selection_field', $mediaSelectionField->getName());
        static::assertEquals([
            'zh-CN' => 'Test media-selection field',
        ], $mediaSelectionField->getLabel());
        static::assertEquals([], $mediaSelectionField->getHelpText());
        static::assertEquals(1, $mediaSelectionField->getPosition());
        static::assertFalse($mediaSelectionField->getRequired());
    }
}
