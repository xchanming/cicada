<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\VersionManager;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\DateTimeDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class CreateAtAndUpdatedAtFieldTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour {
        tearDown as protected tearDownDefinitions;
    }
    use KernelTestBehaviour;

    private Connection $connection;

    private EntityRepository $entityRepository;

    protected function setUp(): void
    {
        $definition = $this->registerDefinition(DateTimeDefinition::class);
        $this->connection = static::getContainer()->get(Connection::class);
        $this->entityRepository = new EntityRepository(
            $definition,
            static::getContainer()->get(EntityReaderInterface::class),
            static::getContainer()->get(VersionManager::class),
            static::getContainer()->get(EntitySearcherInterface::class),
            static::getContainer()->get(EntityAggregatorInterface::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(EntityLoadedEventFactory::class)
        );

        $nullableTable = <<<EOF
DROP TABLE IF EXISTS `date_time_test`;
CREATE TABLE IF NOT EXISTS `date_time_test` (
  `id` varbinary(16) NOT NULL,
  `name` varchar(500) NULL,
  `created_at` datetime(3) NOT NULL,
  `updated_at` datetime(3) NULL,
  PRIMARY KEY `id` (`id`)
);
EOF;
        $this->connection->executeStatement($nullableTable);
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->tearDownDefinitions();
        $this->connection->rollBack();
        $this->connection->executeStatement('DROP TABLE `date_time_test`');
    }

    public function testCreatedAtDefinedAutomatically(): void
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
        ];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);

        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNotNull($entity->get('createdAt'));
        static::assertInstanceOf(\DateTimeInterface::class, $entity->get('createdAt'));
        static::assertNull($entity->get('updatedAt'));
    }

    public function testICanProvideCreatedAt(): void
    {
        $id = Uuid::randomHex();

        $date = new \DateTime('2000-01-01 12:12:12.990');

        $data = [
            'id' => $id,
            'createdAt' => $date,
        ];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);

        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNotNull($entity->get('createdAt'));
        static::assertInstanceOf(\DateTimeInterface::class, $entity->get('createdAt'));

        static::assertEquals(
            $date->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            $entity->get('createdAt')->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        );
    }

    public function testCreatedAtWithNullWillBeOverwritten(): void
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'createdAt' => null,
        ];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);

        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNotNull($entity->get('createdAt'));
        static::assertInstanceOf(\DateTimeInterface::class, $entity->get('createdAt'));
    }

    public function testUpdatedAtWillBeSetAutomatically(): void
    {
        $id = Uuid::randomHex();

        $data = ['id' => $id];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNull($entity->get('updatedAt'));

        $data = ['id' => $id, 'name' => 'updated'];

        $context = Context::createDefaultContext();
        $this->entityRepository->update([$data], $context);
        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNotNull($entity->get('updatedAt'));
    }

    public function testUpdatedAtWithNullWorks(): void
    {
        $id = Uuid::randomHex();

        $data = ['id' => $id];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNull($entity->get('updatedAt'));

        $data = ['id' => $id, 'name' => 'updated', 'updatedAt' => null];

        $context = Context::createDefaultContext();
        $this->entityRepository->update([$data], $context);
        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNotNull($entity->get('updatedAt'));
    }

    public function testUpdatedAtCanNotBeDefined(): void
    {
        $id = Uuid::randomHex();

        $date = new \DateTime('2012-01-01');

        $data = ['id' => $id, 'updatedAt' => $date];

        $context = Context::createDefaultContext();
        $this->entityRepository->create([$data], $context);

        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertNull($entity->get('updatedAt'));

        $data = ['id' => $id, 'name' => 'updated', 'updatedAt' => $date];

        $context = Context::createDefaultContext();
        $this->entityRepository->update([$data], $context);
        $entities = $this->entityRepository->search(new Criteria([$id]), $context);

        static::assertTrue($entities->has($id));

        $entity = $entities->get($id);
        static::assertInstanceOf(ArrayEntity::class, $entity);
        static::assertInstanceOf(\DateTimeInterface::class, $entity->get('updatedAt'));

        static::assertNotEquals(
            $date->format('Y-m-d'),
            $entity->get('updatedAt')->format('Y-m-d')
        );
    }
}
