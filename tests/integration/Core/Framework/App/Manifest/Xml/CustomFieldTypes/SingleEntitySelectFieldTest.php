<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SingleEntitySelectFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $singleEntitySelectField = $this->importCustomField(__DIR__ . '/_fixtures/single-entity-select-field.xml');

        static::assertSame('test_single_entity_select_field', $singleEntitySelectField->getName());
        static::assertSame('entity', $singleEntitySelectField->getType());
        static::assertTrue($singleEntitySelectField->isActive());
        static::assertEquals([
            'label' => [
                'zh-CN' => 'Test single-entity-select field',
            ],
            'helpText' => [],
            'placeholder' => [
                'zh-CN' => 'Choose an entity...',
            ],
            'componentName' => 'sw-entity-single-select',
            'customFieldType' => 'select',
            'customFieldPosition' => 1,
            'entity' => 'product',
        ], $singleEntitySelectField->getConfig());
    }
}
