<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Elasticsearch\Framework\Command;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Elasticsearch\Framework\Command\ElasticsearchAdminResetCommand;
use Cicada\Elasticsearch\Test\AdminElasticsearchTestBehaviour;
use Doctrine\DBAL\Connection;
use OpenSearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class ElasticsearchAdminResetCommandTest extends TestCase
{
    use AdminElasticsearchTestBehaviour;
    use KernelTestBehaviour;
    use QueueTestBehaviour;

    private ElasticsearchAdminResetCommand $adminRefreshIndexCommand;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->adminRefreshIndexCommand = static::getContainer()->get(ElasticsearchAdminResetCommand::class);

        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testExecuteWithInputNo(): void
    {
        $commandTester = new CommandTester($this->adminRefreshIndexCommand);
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $message = $commandTester->getDisplay();

        static::assertStringContainsString('Are you sure you want to reset the Admin Elasticsearch indexing?', $message);
        static::assertStringContainsString('Canceled clearing indexing process', $message);
    }

    public function testExecuteWithInput(): void
    {
        $commandTester = new CommandTester($this->adminRefreshIndexCommand);
        $commandTester->execute([]);

        $message = $commandTester->getDisplay();

        static::assertStringContainsString('Are you sure you want to reset the Admin Elasticsearch indexing?', $message);
        static::assertStringContainsString('Admin Elasticsearch indices deleted and queue cleared', $message);

        $client = $this->getDiContainer()->get(Client::class);
        $client->indices()->get(['index' => EnvironmentHelper::getVariable('CICADA_ADMIN_ES_INDEX_PREFIX') . '*']);

        $tasks = $this->connection->fetchAllAssociative('SELECT `index` FROM admin_elasticsearch_index_task');

        static::assertEmpty($tasks);
    }

    protected function getDiContainer(): ContainerInterface
    {
        return static::getContainer();
    }
}
