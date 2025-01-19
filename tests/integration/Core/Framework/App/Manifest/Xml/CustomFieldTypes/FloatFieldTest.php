<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class FloatFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $floatField = $this->importCustomField(__DIR__ . '/_fixtures/float-field.xml');

        static::assertSame('test_float_field', $floatField->getName());
        static::assertSame('float', $floatField->getType());
        static::assertTrue($floatField->isActive());
        static::assertEquals([
            'type' => 'number',
            'label' => [
                'zh-CN' => 'Test float field',
                'en-GB' => 'Test Kommazahlenfeld',
            ],
            'helpText' => [
                'zh-CN' => 'This is an float field.',
            ],
            'placeholder' => [
                'zh-CN' => 'Enter an float...',
            ],
            'componentName' => 'sw-field',
            'customFieldType' => 'number',
            'customFieldPosition' => 2,
            'numberType' => 'float',
            'min' => 0.5,
            'max' => 1.6,
            'step' => 2.2,
        ], $floatField->getConfig());
    }
}
