<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\VersionManager;
use Cicada\Core\System\CustomEntity\CustomEntityRegistrar;
use Cicada\Core\System\CustomEntity\Schema\DynamicEntityDefinition;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(CustomEntityRegistrar::class)]
class CustomEntityRegistrarTest extends TestCase
{
    public function testSkipsRegistrationIfFetchingCustomEntitiesFailWithException(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willThrowException(new Exception());

        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $definitionInstanceRegistry->expects(static::never())
            ->method('register');

        $container = new Container();
        $container->set(Connection::class, $connection);
        $container->set(DefinitionInstanceRegistry::class, $definitionInstanceRegistry);

        $registrar = new CustomEntityRegistrar($container);

        $registrar->register();

        static::assertCount(3, $container->getServiceIds());
    }

    public function testFetchesCustomEntitiesFromDbAndRegistersThemAtTheContainer(): void
    {
        $container = new Container();

        /** @var DynamicEntityDefinition[] $definitions */
        $definitions = [
            DynamicEntityDefinition::create('ce_test_one', [], [], $container),
            DynamicEntityDefinition::create('ce_test_two', [], [], $container),
        ];

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'name' => 'ce_test_one',
                    'fields' => json_encode([]),
                    'flags' => json_encode([]),
                ],
                [
                    'name' => 'ce_test_two',
                    'fields' => json_encode([]),
                    'flags' => json_encode([]),
                ],
            ]);

        $container->set(Connection::class, $connection);
        $container->set(DefinitionInstanceRegistry::class, new DefinitionInstanceRegistry($container, [], []));
        $container->set(EntityReaderInterface::class, $this->createMock(EntityReaderInterface::class));
        $container->set(VersionManager::class, $this->createMock(VersionManager::class));
        $container->set(EntitySearcherInterface::class, $this->createMock(EntitySearcherInterface::class));
        $container->set(EntityAggregatorInterface::class, $this->createMock(EntityAggregatorInterface::class));
        $container->set('event_dispatcher', $this->createMock(EventDispatcherInterface::class));
        $container->set(EntityLoadedEventFactory::class, $this->createMock(EntityLoadedEventFactory::class));

        $registrar = new CustomEntityRegistrar($container);

        $registrar->register();

        static::assertInstanceOf(DynamicEntityDefinition::class, $definitions[0]);
        static::assertSame('ce_test_one', $definitions[0]->getEntityName());
        static::assertInstanceOf(EntityRepository::class, $container->get($definitions[0]->getEntityName() . '.repository'));

        static::assertInstanceOf(DynamicEntityDefinition::class, $definitions[1]);
        static::assertSame('ce_test_two', $definitions[1]->getEntityName());
        static::assertInstanceOf(EntityRepository::class, $container->get($definitions[1]->getEntityName() . '.repository'));
    }

    public function testAfterMigrationWithEmptyFlags(): void
    {
        $container = new Container();

        /** @var DynamicEntityDefinition[] $definitions */
        $definitions = [
            DynamicEntityDefinition::create('ce_test_one', [], [], $container),
            DynamicEntityDefinition::create('ce_test_two', [], [], $container),
        ];

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'name' => 'ce_test_one',
                    'fields' => json_encode([]),
                    'flags' => '',
                ],
            ]);

        $container->set(Connection::class, $connection);
        $container->set(DefinitionInstanceRegistry::class, new DefinitionInstanceRegistry($container, [], []));
        $container->set(EntityReaderInterface::class, $this->createMock(EntityReaderInterface::class));
        $container->set(VersionManager::class, $this->createMock(VersionManager::class));
        $container->set(EntitySearcherInterface::class, $this->createMock(EntitySearcherInterface::class));
        $container->set(EntityAggregatorInterface::class, $this->createMock(EntityAggregatorInterface::class));
        $container->set('event_dispatcher', $this->createMock(EventDispatcherInterface::class));
        $container->set(EntityLoadedEventFactory::class, $this->createMock(EntityLoadedEventFactory::class));

        $registrar = new CustomEntityRegistrar($container);

        $registrar->register();

        static::assertInstanceOf(DynamicEntityDefinition::class, $definitions[0]);
        static::assertSame('ce_test_one', $definitions[0]->getEntityName());
        static::assertInstanceOf(EntityRepository::class, $container->get($definitions[0]->getEntityName() . '.repository'));
    }
}
