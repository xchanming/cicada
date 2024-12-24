<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\Services;

use Cicada\Core\Framework\DataAbstractionLayer\CompiledFieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\System\UsageData\Services\UsageDataAllowListService;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(UsageDataAllowListService::class)]
class UsageDataAllowListServiceTest extends TestCase
{
    public function testGetDefaultUsageDataAllowList(): void
    {
        $list = UsageDataAllowListService::getDefaultUsageDataAllowList();

        static::assertNotEmpty($list);
    }

    public function testItFiltersEntity(): void
    {
        $definition = $this->createMock(EntityDefinition::class);
        $definition->method('getEntityName')
            ->willReturn('not_allowed_entity');

        $service = new UsageDataAllowListService();
        $selectedFields = $service->getFieldsToSelectFromDefinition($definition);

        static::assertCount(0, $selectedFields);
    }

    public function testItFiltersFields(): void
    {
        $definition = new ProductMockEntityDefinition();

        $registry = new StaticDefinitionInstanceRegistry(
            [$definition],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class),
        );

        $fields = new FieldCollection([
            new StringField('not_allowed', 'not_in_usage_data_allow_list'),
        ]);

        $definition->setFields(new CompiledFieldCollection($registry, $fields));

        $service = new UsageDataAllowListService();
        $selectedFields = $service->getFieldsToSelectFromDefinition($definition);

        static::assertCount(0, $selectedFields);
    }

    public function testItAddsFields(): void
    {
        $fields = new FieldCollection([
            new StringField('id', 'id'),
        ]);

        $definition = new ProductMockEntityDefinition();

        $registry = new StaticDefinitionInstanceRegistry(
            [$definition],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class),
        );

        $definition->setFields(new CompiledFieldCollection($registry, $fields));

        $service = new UsageDataAllowListService();
        $selectedFields = $service->getFieldsToSelectFromDefinition($definition);

        static::assertCount(1, $selectedFields);
    }

    public function testEntityIsNotAllowed(): void
    {
        $service = new UsageDataAllowListService();
        static::assertFalse($service->isEntityAllowed('not_allowed'));
    }

    public function testEntityIsAllowed(): void
    {
        $service = new UsageDataAllowListService();
        static::assertTrue($service->isEntityAllowed('product'));
    }
}

/**
 * @internal
 */
class ProductMockEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'product';
    }

    public function setFields(CompiledFieldCollection $fields): void
    {
        $this->fields = $fields;
    }

    protected function defineFields(): FieldCollection
    {
        // @phpstan-ignore-next-line
        return $this->fields;
    }
}
