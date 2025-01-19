<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Manifest\Xml\CustomFieldTypes;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\App\CustomFieldTypeTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SingleSelectFieldTest extends TestCase
{
    use CustomFieldTypeTestBehaviour;
    use IntegrationTestBehaviour;

    public function testToEntityArray(): void
    {
        $singleSelectField = $this->importCustomField(__DIR__ . '/_fixtures/single-select-field.xml');

        static::assertSame('test_single_select_field', $singleSelectField->getName());
        static::assertSame('select', $singleSelectField->getType());
        static::assertTrue($singleSelectField->isActive());
        static::assertEquals([
            'label' => [
                'zh-CN' => 'Test single-select field',
            ],
            'helpText' => [],
            'placeholder' => [
                'zh-CN' => 'Choose an option...',
            ],
            'componentName' => 'sw-single-select',
            'customFieldType' => 'select',
            'customFieldPosition' => 1,
            'options' => [
                [
                    'label' => [
                        'zh-CN' => 'First',
                        'en-GB' => 'Erster',
                    ],
                    'value' => 'first',
                ],
                [
                    'label' => [
                        'zh-CN' => 'Second',
                    ],
                    'value' => 'second',
                ],
            ],
        ], $singleSelectField->getConfig());
    }
}
