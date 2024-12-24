<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Elasticsearch\Admin;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Elasticsearch\Admin\AdminElasticsearchHelper;
use Cicada\Elasticsearch\Admin\AdminIndexingBehavior;
use Cicada\Elasticsearch\Admin\AdminSearchRegistry;
use Cicada\Elasticsearch\Admin\Indexer\PromotionAdminSearchIndexer;
use Cicada\Elasticsearch\Test\AdminElasticsearchTestBehaviour;
use Doctrine\DBAL\Connection;
use OpenSearch\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Group('skip-paratest')]
class AdminSearchRegistryTest extends TestCase
{
    use AdminApiTestBehaviour;
    use AdminElasticsearchTestBehaviour;
    use KernelTestBehaviour;
    use QueueTestBehaviour;

    private Connection $connection;

    private AdminSearchRegistry $registry;

    private Client $client;

    protected function setUp(): void
    {
        $this->clearElasticsearch();

        $this->connection = static::getContainer()->get(Connection::class);

        $this->client = static::getContainer()->get(Client::class);

        $indexer = new PromotionAdminSearchIndexer(
            $this->connection,
            static::getContainer()->get(IteratorFactory::class),
            static::getContainer()->get('promotion.repository'),
            100
        );

        $searchHelper = new AdminElasticsearchHelper(true, true, 'sw-admin');
        $this->registry = new AdminSearchRegistry(
            ['promotion' => $indexer],
            $this->connection,
            $this->getDiContainer()->get(MessageBusInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->client,
            $searchHelper,
            $this->createMock(LoggerInterface::class),
            [],
            []
        );
    }

    public function testIterate(): void
    {
        $c = static::getContainer()->get(Connection::class);
        static::assertEmpty($c->fetchAllAssociative('SELECT `index` FROM `admin_elasticsearch_index_task`'));

        $this->registry->iterate(new AdminIndexingBehavior(true));

        $index = $c->fetchOne('SELECT `index` FROM `admin_elasticsearch_index_task`');

        static::assertNotFalse($index);

        static::assertTrue($this->client->indices()->exists(['index' => $index]));

        $indices = array_values($this->client->indices()->getMapping(['index' => $index]))[0];
        $properties = $indices['mappings']['properties'];

        $expectedProperties = [
            'id' => ['type' => 'keyword'],
            'text' => ['type' => 'text'],
            'entityName' => ['type' => 'keyword'],
            'parameters' => ['type' => 'keyword'],
            'textBoosted' => ['type' => 'text'],
        ];

        static::assertEquals($expectedProperties, $properties);
    }

    public function testRefresh(): void
    {
        $c = static::getContainer()->get(Connection::class);
        static::assertEmpty($c->fetchAllAssociative('SELECT `index` FROM `admin_elasticsearch_index_task`'));

        $this->registry->refresh(new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([
            new EntityWrittenEvent('promotion', [
                new EntityWriteResult(
                    'c1a28776116d4431a2208eb2960ec340',
                    [],
                    'promotion',
                    EntityWriteResult::OPERATION_INSERT
                ),
            ], Context::createDefaultContext()),
        ]), []));

        $this->runWorker();

        $index = $c->fetchOne('SELECT `index` FROM `admin_elasticsearch_index_task`');

        static::assertNotFalse($index);

        static::assertTrue($this->client->indices()->exists(['index' => $index]));

        $indices = array_values($this->client->indices()->getMapping(['index' => $index]))[0];
        $properties = $indices['mappings']['properties'];

        $expectedProperties = [
            'id' => ['type' => 'keyword'],
            'text' => ['type' => 'text'],
            'entityName' => ['type' => 'keyword'],
            'parameters' => ['type' => 'keyword'],
            'textBoosted' => ['type' => 'text'],
        ];

        static::assertEquals($expectedProperties, $properties);
    }

    protected function getDiContainer(): ContainerInterface
    {
        return static::getContainer();
    }
}
