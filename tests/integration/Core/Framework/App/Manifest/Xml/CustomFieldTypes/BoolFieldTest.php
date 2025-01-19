<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BoolFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $boolField = $this->importCustomField(__DIR__ . '/_fixtures/bool-field.xml');

        static::assertSame('test_bool_field', $boolField->getName());
        static::assertSame('bool', $boolField->getType());
        static::assertTrue($boolField->isActive());
        static::assertEquals([
            'type' => 'checkbox',
            'label' => [
                'zh-CN' => 'Test bool field',
            ],
            'helpText' => [],
            'componentName' => 'sw-field',
            'customFieldType' => 'checkbox',
            'customFieldPosition' => 1,
        ], $boolField->getConfig());
    }
}
