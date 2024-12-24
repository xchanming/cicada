<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\Api;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexingMessage;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Increment\AbstractIncrementer;
use Cicada\Core\Framework\Increment\IncrementGatewayRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\TestTask;

/**
 * @internal
 */
#[Package('services-settings')]
class ConsumeMessagesControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;
    use QueueTestBehaviour;

    private AbstractIncrementer $incrementer;

    protected function setUp(): void
    {
        $this->incrementer = static::getContainer()->get('cicada.increment.gateway.registry')->get(IncrementGatewayRegistry::MESSAGE_QUEUE_POOL);
    }

    public function testConsumeMessages(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        // queue a task
        $repo = static::getContainer()->get('scheduled_task.repository');
        $taskId = Uuid::randomHex();
        $repo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 300,
                'defaultRunInterval' => 300,
                'status' => ScheduledTaskDefinition::STATUS_SCHEDULED,
                'nextExecutionTime' => (new \DateTime())->modify('-1 second'),
            ],
        ], Context::createDefaultContext());

        $url = '/api/_action/scheduled-task/run';
        $client = $this->getBrowser();
        $client->request('POST', $url);

        // consume the queued task
        $url = '/api/_action/message-queue/consume';
        $client = $this->getBrowser();
        $client->request('POST', $url, ['receiver' => 'async']);

        $response = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(200, $client->getResponse()->getStatusCode(), \print_r($response, true));
        static::assertArrayHasKey('handledMessages', $response);
        static::assertIsInt($response['handledMessages']);
        static::assertEquals(1, $response['handledMessages']);
    }

    public function testMessageStatsDecrement(): void
    {
        $messageBus = static::getContainer()->get('messenger.bus.cicada');
        $message = new ProductIndexingMessage([Uuid::randomHex()]);
        $messageBus->dispatch($message);

        $gateway = static::getContainer()->get('cicada.increment.gateway.registry');
        $entries = $gateway->get(IncrementGatewayRegistry::MESSAGE_QUEUE_POOL)->list('message_queue_stats');

        static::assertArrayHasKey(ProductIndexingMessage::class, $entries);
        static::assertGreaterThan(0, $entries[ProductIndexingMessage::class]['count']);

        $url = '/api/_action/message-queue/consume';
        $client = $this->getBrowser();
        $client->request('POST', $url, ['receiver' => 'async']);

        $entries = $this->incrementer->list('message_queue_stats');

        static::assertArrayHasKey(ProductIndexingMessage::class, $entries);
        static::assertEquals(0, $entries[ProductIndexingMessage::class]['count']);
    }
}
