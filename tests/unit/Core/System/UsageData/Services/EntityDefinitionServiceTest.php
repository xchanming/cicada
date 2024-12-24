<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\UsageData\Services\EntityDefinitionService;
use Cicada\Core\System\UsageData\Services\UsageDataAllowListService;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('data-services')]
#[CoversClass(EntityDefinitionService::class)]
class EntityDefinitionServiceTest extends TestCase
{
    /**
     * @var array<class-string<EntityDefinition>, EntityDefinition>
     */
    private array $definitionsByName = [];

    public function testGetEntityDefinition(): void
    {
        $productDefinition = new ProductDefinition();
        $categoryDefinition = new CategoryDefinition();

        new StaticDefinitionInstanceRegistry(
            [
                $productDefinition,
                $categoryDefinition,
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class),
        );

        $entityDefinitionService = new EntityDefinitionService(
            [
                $productDefinition,
                $categoryDefinition,
            ],
            new UsageDataAllowListService(),
        );

        static::assertSame($productDefinition, $entityDefinitionService->getAllowedEntityDefinition($productDefinition->getEntityName()));
        static::assertSame($categoryDefinition, $entityDefinitionService->getAllowedEntityDefinition($categoryDefinition->getEntityName()));
        static::assertNull($entityDefinitionService->getAllowedEntityDefinition('this-entity-does-not-exists'));
    }

    public function testGetEntityDefinitions(): void
    {
        $definitions = [
            new ProductDefinition(),
            new CategoryDefinition(),
        ];
        new StaticDefinitionInstanceRegistry(
            $definitions,
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class),
        );

        $entityDefinitionService = new EntityDefinitionService($definitions, new UsageDataAllowListService());

        static::assertSame($definitions, $entityDefinitionService->getAllowedEntityDefinitions());
    }

    public function testManyToManyAssociationFieldWithoutIdField(): void
    {
        $this->initDefinitions();
        $entityDefinitionService = new EntityDefinitionService($this->definitionsByName, new UsageDataAllowListService());

        $result = $entityDefinitionService->getManyToManyAssociationIdFields($this->definitionsByName[EntityWithManyToManyWithoutIdFieldDefinition::class]->getFields());

        static::assertCount(1, $result);
        static::assertArrayHasKey('associationField', $result[0]);
        static::assertNull($result[0]['idField']);
    }

    public function testManyToManyAssociationFieldWithIdField(): void
    {
        $this->initDefinitions();
        $entityDefinitionService = new EntityDefinitionService($this->definitionsByName, new UsageDataAllowListService());

        $result = $entityDefinitionService->getManyToManyAssociationIdFields($this->definitionsByName[EntityWithManyToManyWithIdFieldDefinition::class]->getFields());
        static::assertCount(1, $result);
        static::assertInstanceOf(ManyToManyIdField::class, $result[0]['idField']);
        static::assertSame('EntityWithManyToManyWithIdFieldAssociationName', $result[0]['idField']->getAssociationName());
    }

    #[DataProvider('provideEntityDefinitions')]
    public function testNewsletterRecipientDefinitionIsPuidEntity(
        EntityDefinition $entityDefinition,
        bool $isPuidEntity,
        string $errorMessage,
    ): void {
        new StaticDefinitionInstanceRegistry(
            [$entityDefinition],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $service = new EntityDefinitionService([$entityDefinition], new UsageDataAllowListService());

        static::assertSame($isPuidEntity, $service->isPuidEntity($entityDefinition), $errorMessage);
    }

    /**
     * @return list<array{EntityDefinition, bool}>
     */
    public static function provideEntityDefinitions(): array
    {
        $dataset = [
            ProductDefinition::class => false,
            NewsletterRecipientDefinition::class => true,
            CustomerDefinition::class => true,
        ];

        $result = [];
        foreach ($dataset as $class => $isPuid) {
            $result[] = [
                new $class(),
                $isPuid,
                \sprintf('Entity "%s" should %sbe a PUID entity', $class, $isPuid ? '' : 'not '),
            ];
        }

        return $result;
    }

    private function initDefinitions(): void
    {
        $definitions = [
            EntityWithManyToManyWithoutIdFieldDefinition::class,
            MockEntityDefinition::class,
            ManyToManyMappingEntityDefinition::class,
            EntityWithManyToManyWithIdFieldDefinition::class,
            ProductDefinition::class,
            NewsletterRecipientDefinition::class,
            CustomerDefinition::class,
        ];

        foreach ($definitions as $definition) {
            $this->definitionsByName[$definition] = new $definition();
        }

        $this->compileDefinitions(array_values($this->definitionsByName));
    }

    /**
     * @param array<int|string, EntityDefinition> $entityDefinitions
     */
    private function compileDefinitions(array $entityDefinitions): void
    {
        $registry = new StaticDefinitionInstanceRegistry(
            $entityDefinitions,
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        foreach ($entityDefinitions as $entityDefinition) {
            $entityDefinition->compile($registry);
        }
    }
}

/**
 * @internal
 */
class EntityWithManyToManyWithoutIdFieldDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'EntityWithManyToManyWithoutIdField';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new ManyToManyAssociationField('manyToMany', MockEntityDefinition::class, ManyToManyMappingEntityDefinition::class, 'manyToMany', 'manyToMany'),
        ]);
    }
}

/**
 * @internal
 */
class EntityWithManyToManyWithIdFieldDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'EntityWithManyToManyWithIdField';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new ManyToManyIdField('manyToManyIds', 'manyToManyIds', 'EntityWithManyToManyWithIdFieldAssociationName'),
            new ManyToManyAssociationField('EntityWithManyToManyWithIdFieldAssociationName', MockEntityDefinition::class, ManyToManyMappingEntityDefinition::class, 'manyToMany', 'manyToMany'),
        ]);
    }
}

/**
 * @internal
 */
class MockEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'MockEntity';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([]);
    }
}

/**
 * @internal
 */
class ManyToManyMappingEntityDefinition extends MappingEntityDefinition
{
    public function getEntityName(): string
    {
        return 'ManyToManyEntity';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([]);
    }
}
