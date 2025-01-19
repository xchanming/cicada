<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MultiEntitySelectFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $multiEntitySelectField = $this->importCustomField(__DIR__ . '/_fixtures/multi-entity-select-field.xml');

        static::assertSame('test_multi_entity_select_field', $multiEntitySelectField->getName());
        static::assertSame('entity', $multiEntitySelectField->getType());
        static::assertTrue($multiEntitySelectField->isActive());
        static::assertEquals([
            'label' => [
                'zh-CN' => 'Test multi-entity-select field',
            ],
            'helpText' => [],
            'placeholder' => [
                'zh-CN' => 'Choose an entity...',
            ],
            'componentName' => 'sw-entity-multi-id-select',
            'customFieldType' => 'select',
            'customFieldPosition' => 1,
            'entity' => 'product',
        ], $multiEntitySelectField->getConfig());
    }
}
