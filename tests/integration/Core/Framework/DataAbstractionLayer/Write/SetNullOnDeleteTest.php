<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Write;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\VersionManager;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Write\Entity\SetNullOnDeleteChildDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Write\Entity\SetNullOnDeleteManyToOneDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Write\Entity\SetNullOnDeleteParentDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class SetNullOnDeleteTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityWriter $writer;

    private EntityRepository $repository;

    private Connection $connection;

    private SetNullOnDeleteParentDefinition $setNullOnDeleteParentDefinition;

    protected function setUp(): void
    {
        $this->writer = static::getContainer()->get(EntityWriter::class);
        $this->connection = static::getContainer()->get(Connection::class);

        $registry = static::getContainer()->get(DefinitionInstanceRegistry::class);

        $registry->register(new SetNullOnDeleteParentDefinition());
        $registry->register(new SetNullOnDeleteManyToOneDefinition());
        $registry->register(new SetNullOnDeleteChildDefinition());

        $definition = static::getContainer()->get(SetNullOnDeleteParentDefinition::class);
        static::assertInstanceOf(SetNullOnDeleteParentDefinition::class, $definition);

        $this->setNullOnDeleteParentDefinition = $definition;

        $this->repository = new EntityRepository(
            $this->setNullOnDeleteParentDefinition,
            static::getContainer()->get(EntityReaderInterface::class),
            static::getContainer()->get(VersionManager::class),
            static::getContainer()->get(EntitySearcherInterface::class),
            static::getContainer()->get(EntityAggregatorInterface::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(EntityLoadedEventFactory::class)
        );

        $this->connection->rollBack();

        $this->connection->executeStatement(
            'DROP TABLE IF EXISTS set_null_on_delete_child;
             DROP TABLE IF EXISTS set_null_on_delete_parent;
             DROP TABLE IF EXISTS set_null_on_delete_many_to_one;'
        );

        $this->connection->executeStatement(
            'CREATE TABLE `set_null_on_delete_parent` (
               `id` binary(16) NOT NULL,
               `set_null_on_delete_many_to_one_id` binary(16) NULL,
               `name` varchar(255) NOT NULL,
               `version_id` binary(16) NOT NULL,
               `created_at` DATETIME(3) NOT NULL,
               `updated_at` DATETIME(3) NULL,
               PRIMARY KEY `primary` (`id`, `version_id`)
             );'
        );

        $this->connection->executeStatement(
            'CREATE TABLE `set_null_on_delete_child` (
               `id` binary(16) NOT NULL,
               `set_null_on_delete_parent_id` binary(16) NULL,
               `set_null_on_delete_parent_version_id` binary(16) NULL,
               `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
               `created_at` DATETIME(3) NOT NULL,
               `updated_at` DATETIME(3) NULL,
               KEY `set_null_on_delete_parent_id` (`set_null_on_delete_parent_id`,`set_null_on_delete_parent_version_id`),
               CONSTRAINT `set_null_on_delete_child_ibfk_1` FOREIGN KEY (`set_null_on_delete_parent_id`, `set_null_on_delete_parent_version_id`)
                   REFERENCES `set_null_on_delete_parent` (`id`, `version_id`) ON DELETE SET NULL,
               PRIMARY KEY `primary` (`id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $this->connection->executeStatement(
            'CREATE TABLE `set_null_on_delete_many_to_one` (
               `id` binary(16) NOT NULL,
               `name` varchar(255) NOT NULL,
               `created_at` DATETIME(3) NOT NULL,
               `updated_at` DATETIME(3) NULL,
               PRIMARY KEY `primary` (`id`)
             );'
        );

        $this->connection->executeStatement(
            'ALTER TABLE `set_null_on_delete_parent`
             ADD FOREIGN KEY (`set_null_on_delete_many_to_one_id`) REFERENCES `set_null_on_delete_many_to_one` (`id`) ON DELETE SET NULL;'
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

        $this->connection->executeStatement(
            'DROP TABLE IF EXISTS set_null_on_delete_child;
             DROP TABLE IF EXISTS set_null_on_delete_parent;
             DROP TABLE IF EXISTS set_null_on_delete_many_to_one;'
        );

        $this->connection->beginTransaction();
    }

    public function testDeleteOneToManyIfParentHasVersionId(): void
    {
        $ids = new IdsCollection();

        $this->writer->insert(
            $this->setNullOnDeleteParentDefinition,
            [
                [
                    'id' => $ids->get('parent'),
                    'productNumber' => Uuid::randomHex(),
                    'name' => 'test',
                    'setNulls' => [
                        ['id' => $ids->get('child'), 'name' => 'test child'],
                    ],
                ],
            ],
            WriteContext::createFromContext(Context::createDefaultContext())
        );

        $parents = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_parent');
        static::assertCount(1, $parents);

        $children = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_child');
        static::assertCount(1, $children);

        $result = $this->writer->delete(
            $this->setNullOnDeleteParentDefinition,
            [
                ['id' => $ids->get('parent')],
            ],
            WriteContext::createFromContext(Context::createDefaultContext())
        );

        $deleted = $result->getDeleted();
        static::assertCount(1, $deleted);
        static::assertArrayHasKey(SetNullOnDeleteParentDefinition::ENTITY_NAME, $deleted);

        static::assertCount(1, $deleted[SetNullOnDeleteParentDefinition::ENTITY_NAME]);
        static::assertEquals($ids->get('parent'), $deleted[SetNullOnDeleteParentDefinition::ENTITY_NAME][0]->getPrimaryKey());

        $updated = $result->getWritten();
        static::assertCount(1, $updated);
        static::assertArrayHasKey(SetNullOnDeleteChildDefinition::ENTITY_NAME, $updated);

        static::assertCount(1, $updated[SetNullOnDeleteChildDefinition::ENTITY_NAME]);
        /** @var EntityWriteResult $updateResult */
        $updateResult = $updated[SetNullOnDeleteChildDefinition::ENTITY_NAME][0];
        static::assertEquals($ids->get('child'), $updateResult->getPrimaryKey());
        static::assertEquals([
            'id' => $ids->get('child'),
            'setNullOnDeleteParentId' => null,
            'setNullOnDeleteParentVersionId' => null,
        ], $updateResult->getPayload());

        $parents = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_parent');
        static::assertCount(0, $parents);

        $children = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_child');
        static::assertCount(1, $children);
        static::assertNull($children[0]['set_null_on_delete_parent_id']);
        static::assertNull($children[0]['set_null_on_delete_parent_version_id']);
    }

    public function testDeleteManyToOne(): void
    {
        $id = Uuid::randomHex();
        $childId = Uuid::randomHex();

        $this->writer->insert(
            $this->setNullOnDeleteParentDefinition,
            [
                [
                    'id' => $id,
                    'productNumber' => Uuid::randomHex(),
                    'name' => 'test',
                    'manyToOne' => [
                        'id' => $childId,
                        'name' => 'test child',
                    ],
                ],
            ],
            WriteContext::createFromContext(Context::createDefaultContext())
        );

        $parents = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_parent');
        static::assertCount(1, $parents);

        $manyToOne = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_many_to_one');
        static::assertCount(1, $manyToOne);

        $definition = static::getContainer()->get(SetNullOnDeleteManyToOneDefinition::class);
        static::assertInstanceOf(SetNullOnDeleteManyToOneDefinition::class, $definition);

        $result = $this->writer->delete(
            $definition,
            [
                ['id' => $childId],
            ],
            WriteContext::createFromContext(Context::createDefaultContext())
        );

        $deleted = $result->getDeleted();
        static::assertCount(1, $deleted);
        static::assertArrayHasKey(SetNullOnDeleteManyToOneDefinition::ENTITY_NAME, $deleted);

        static::assertCount(1, $deleted[SetNullOnDeleteManyToOneDefinition::ENTITY_NAME]);
        static::assertEquals($childId, $deleted[SetNullOnDeleteManyToOneDefinition::ENTITY_NAME][0]->getPrimaryKey());

        $updated = $result->getWritten();
        static::assertCount(1, $updated);
        static::assertArrayHasKey(SetNullOnDeleteParentDefinition::ENTITY_NAME, $updated);

        static::assertCount(1, $updated[SetNullOnDeleteParentDefinition::ENTITY_NAME]);
        /** @var EntityWriteResult $updateResult */
        $updateResult = $updated[SetNullOnDeleteParentDefinition::ENTITY_NAME][0];
        static::assertEquals($id, $updateResult->getPrimaryKey());
        static::assertEquals([
            'id' => $id,
            'versionId' => Defaults::LIVE_VERSION,
            'setNullOnDeleteManyToOneId' => null,
        ], $updateResult->getPayload());

        $parents = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_parent');
        static::assertCount(1, $parents);
        static::assertNull($parents[0]['set_null_on_delete_many_to_one_id']);

        $manyToOne = $this->connection->fetchAllAssociative('SELECT * FROM set_null_on_delete_many_to_one');
        static::assertCount(0, $manyToOne);
    }

    public function testSetNullOnDeleteThrowsWrittenEvent(): void
    {
        $id = Uuid::randomHex();
        $childId = Uuid::randomHex();

        $this->repository->create(
            [
                [
                    'id' => $id,
                    'productNumber' => Uuid::randomHex(),
                    'name' => 'test',
                    'setNulls' => [
                        ['id' => $childId, 'name' => 'test child'],
                    ],
                ],
            ],
            Context::createDefaultContext()
        );

        $eventWasThrown = false;

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            SetNullOnDeleteChildDefinition::ENTITY_NAME . '.written',
            static function (EntityWrittenEvent $event) use ($childId, &$eventWasThrown): void {
                static::assertCount(1, $event->getPayloads());
                static::assertEquals(
                    [
                        'id' => $childId,
                        'setNullOnDeleteParentId' => null,
                        'setNullOnDeleteParentVersionId' => null,
                    ],
                    $event->getPayloads()[0]
                );

                $eventWasThrown = true;
            }
        );

        $this->repository->delete([['id' => $id]], Context::createDefaultContext());

        static::assertTrue($eventWasThrown);
    }
}
