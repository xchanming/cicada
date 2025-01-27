<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class IntFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $intField = $this->importCustomField(__DIR__ . '/_fixtures/int-field.xml');

        static::assertSame('test_int_field', $intField->getName());
        static::assertSame('int', $intField->getType());
        static::assertTrue($intField->isActive());
        static::assertEquals([
            'type' => 'number',
            'label' => [
                'zh-CN' => 'Test int field',
                'en-GB' => 'Test Ganzzahlenfeld',
            ],
            'helpText' => [
                'zh-CN' => 'This is an int field.',
            ],
            'placeholder' => [
                'zh-CN' => 'Enter an int...',
            ],
            'componentName' => 'sw-field',
            'customFieldType' => 'number',
            'customFieldPosition' => 1,
            'numberType' => 'int',
            'min' => 0,
            'max' => 1,
            'step' => 2,
            'validation' => 'required',
        ], $intField->getConfig());
    }
}
