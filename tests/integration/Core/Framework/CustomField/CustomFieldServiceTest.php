<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\CustomField;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\CustomField\CustomFieldCollection;
use Cicada\Core\System\CustomField\CustomFieldService;
use Cicada\Core\System\CustomField\CustomFieldTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CustomFieldServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<CustomFieldCollection>
     */
    private EntityRepository $attributeRepository;

    private CustomFieldService $attributeService;

    protected function setUp(): void
    {
        $this->attributeRepository = static::getContainer()->get('custom_field.repository');
        $this->attributeService = static::getContainer()->get(CustomFieldService::class);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function attributeFieldTestProvider(): array
    {
        return [
            [
                CustomFieldTypes::BOOL, BoolField::class,
                CustomFieldTypes::DATETIME, DateTimeField::class,
                CustomFieldTypes::FLOAT, FloatField::class,
                CustomFieldTypes::HTML, LongTextField::class,
                CustomFieldTypes::INT, IntField::class,
                CustomFieldTypes::JSON, JsonField::class,
                CustomFieldTypes::TEXT, LongTextField::class,
            ],
        ];
    }

    /**
     * @param class-string<Field> $expectedFieldClass
     */
    #[DataProvider('attributeFieldTestProvider')]
    public function testGetCustomFieldField(string $attributeType, string $expectedFieldClass): void
    {
        $attribute = [
            'name' => 'test_attr',
            'type' => $attributeType,
        ];
        $this->attributeRepository->create([$attribute], Context::createDefaultContext());

        static::assertInstanceOf(
            $expectedFieldClass,
            $this->attributeService->getCustomField('test_attr')
        );
    }

    public function testOnlyGetActive(): void
    {
        $id = Uuid::randomHex();
        $this->attributeRepository->upsert([[
            'id' => $id,
            'name' => 'test_attr',
            'active' => false,
            'type' => CustomFieldTypes::TEXT,
        ]], Context::createDefaultContext());

        $actual = $this->attributeService->getCustomField('test_attr');
        static::assertNull($actual);

        $this->attributeRepository->upsert([[
            'id' => $id,
            'active' => true,
        ]], Context::createDefaultContext());
        $actual = $this->attributeService->getCustomField('test_attr');
        static::assertNotNull($actual);
    }
}
