<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Elasticsearch\Product;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\CustomField\CustomFieldTypes;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Elasticsearch\Product\CustomFieldSetGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomFieldSetGateway::class)]
class CustomFieldSetGatewayTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $customFieldRepository = static::getContainer()->get('custom_field_set.repository');

        $customFieldRepository->create([
            [
                'id' => $this->ids->get('custom-field-set-1'),
                'name' => 'swag_example_set1',
                'config' => [
                    'label' => [
                        'en-GB' => 'English custom field set label',
                        'zh-CN' => 'German custom field set label',
                    ],
                ],
                'relations' => [
                    ['entityName' => 'product'],
                    ['entityName' => 'customer'],
                ],
                'customFields' => [
                    [
                        'id' => $this->ids->get('custom-field-1'),
                        'name' => 'test_newly_created_field',
                        'type' => CustomFieldTypes::INT,
                    ],
                    [
                        'id' => $this->ids->get('custom-field-2'),
                        'name' => 'test_newly_created_field_text',
                        'type' => CustomFieldTypes::TEXT,
                    ],
                ],
            ],
            [
                'id' => $this->ids->get('custom-field-set-2'),
                'name' => 'swag_example_set2',
                'config' => [
                    'label' => [
                        'en-GB' => 'English custom field set label',
                        'zh-CN' => 'German custom field set label',
                    ],
                ],
                'relations' => [
                    ['entityName' => 'product'],
                ],
                'customFields' => [
                    [
                        'id' => $this->ids->get('custom-field-3'),
                        'name' => 'test_newly_created_field3',
                        'type' => CustomFieldTypes::INT,
                    ],
                ],
            ],
        ], Context::createDefaultContext());
    }

    protected function tearDown(): void
    {
        $customFieldRepository = static::getContainer()->get('custom_field_set.repository');

        $customFieldRepository->delete([
            ['id' => $this->ids->get('custom-field-set-1')],
            ['id' => $this->ids->get('custom-field-set-2')],
        ], Context::createDefaultContext());
    }

    public function testFetchCustomFieldsForSets(): void
    {
        $result = static::getContainer()
            ->get(CustomFieldSetGateway::class)
            ->fetchCustomFieldsForSets([
                $this->ids->get('custom-field-set-1'),
            ]);

        static::assertSame([
            $this->ids->get('custom-field-set-1') => [
                [
                    'id' => $this->ids->get('custom-field-1'),
                    'name' => 'test_newly_created_field',
                    'type' => 'int',
                ],
                [
                    'id' => $this->ids->get('custom-field-2'),
                    'name' => 'test_newly_created_field_text',
                    'type' => 'text',
                ],
            ],
        ], $result);
    }

    public function testFetchFieldSetIds(): void
    {
        $result = static::getContainer()
            ->get(CustomFieldSetGateway::class)
            ->fetchFieldSetIds([
                $this->ids->get('custom-field-1'),
                $this->ids->get('custom-field-2'),
                $this->ids->get('custom-field-3'),
            ]);

        static::assertSame([
            $this->ids->get('custom-field-1') => $this->ids->get('custom-field-set-1'),
            $this->ids->get('custom-field-2') => $this->ids->get('custom-field-set-1'),
            $this->ids->get('custom-field-3') => $this->ids->get('custom-field-set-2'),
        ], $result);
    }

    public function testFetchFieldSetEntityMappings(): void
    {
        $result = static::getContainer()
            ->get(CustomFieldSetGateway::class)
            ->fetchFieldSetEntityMappings([
                $this->ids->get('custom-field-set-1'),
                $this->ids->get('custom-field-set-2'),
            ]);

        static::assertSame([
            $this->ids->get('custom-field-set-1') => ['customer', 'product'],
            $this->ids->get('custom-field-set-2') => ['product'],
        ], $result);
    }
}
