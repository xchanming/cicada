<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\CustomField;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomFields::class)]
class CustomFieldsTest extends TestCase
{
    public function testFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../_fixtures/test/manifest.xml');

        static::assertNotNull($manifest->getCustomFields());
        static::assertCount(1, $manifest->getCustomFields()->getCustomFieldSets());

        $customFieldSet = $manifest->getCustomFields()->getCustomFieldSets()[0];
        static::assertEquals('custom_field_test', $customFieldSet->getName());
        static::assertEquals([
            'zh-CN' => 'Custom field test',
            'en-GB' => 'Zusatzfeld Test',
        ], $customFieldSet->getLabel());
        static::assertEquals(['product', 'customer'], $customFieldSet->getRelatedEntities());
        static::assertTrue($customFieldSet->getGlobal());

        static::assertCount(2, $customFieldSet->getFields());

        $fields = $customFieldSet->getFields();

        static::assertSame('bla_test', $fields[0]->getName());
        static::assertFalse($fields[0]->isAllowCustomerWrite());
        static::assertFalse($fields[0]->isAllowCartExpose());

        static::assertSame('bla_test2', $fields[1]->getName());
        static::assertTrue($fields[1]->isAllowCustomerWrite());
        static::assertTrue($fields[1]->isAllowCartExpose());
    }
}
