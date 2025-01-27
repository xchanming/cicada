<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Cicada\Core\Framework\App\Exception\CustomFieldTypeNotFoundException;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes\CustomFieldTypeFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomFieldTypeFactory::class)]
class CustomFieldTypeFactoryTest extends TestCase
{
    public function testCreateFromXmlThrowsExceptionOnInvalidTag(): void
    {
        $this->expectException(CustomFieldTypeNotFoundException::class);
        $this->expectExceptionMessage('CustomFieldType for XML-Element "invalid" not found.');
        CustomFieldTypeFactory::createFromXml(new \DOMElement('invalid'));
    }

    public function testTranslatedForTag(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/custom-field-type-factory.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];

        static::assertCount(1, $customFieldSet->getFields());

        $field = $customFieldSet->getFields()[0];
        static::assertEquals('bool_field', $field->getName());
        static::assertEquals([
            'zh-CN' => 'Test bool field',
            'en-GB' => 'Test bool field',
        ], $field->getLabel());
        static::assertEquals([
            'zh-CN' => 'Help text',
            'en-GB' => 'Help text',
        ], $field->getHelpText());
    }
}
