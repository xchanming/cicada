<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\Api;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Cicada\Core\Framework\Test\MessageQueue\fixtures\FooMessage;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\TestTask;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class ScheduledTaskControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;
    use QueueTestBehaviour;

    public function testRunScheduledTasks(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

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

        static::assertSame(200, $client->getResponse()->getStatusCode());
        static::assertSame(json_encode(['message' => 'Success']), $client->getResponse()->getContent());

        /** @var ScheduledTaskEntity $task */
        $task = $repo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);
        static::assertEquals(ScheduledTaskDefinition::STATUS_QUEUED, $task->getStatus());
    }

    public function testRunSkippedTasks(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $repo = static::getContainer()->get('scheduled_task.repository');
        $taskId = Uuid::randomHex();
        $repo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 300,
                'defaultRunInterval' => 300,
                'status' => ScheduledTaskDefinition::STATUS_SKIPPED,
                'nextExecutionTime' => (new \DateTime())->modify('-1 second'),
            ],
        ], Context::createDefaultContext());

        $url = '/api/_action/scheduled-task/run';
        $client = $this->getBrowser();
        $client->request('POST', $url);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        static::assertSame(json_encode(['message' => 'Success']), $client->getResponse()->getContent());

        /** @var ScheduledTaskEntity $task */
        $task = $repo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);
        static::assertEquals(ScheduledTaskDefinition::STATUS_QUEUED, $task->getStatus());
    }

    public function testGetMinRunInterval(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $repo = static::getContainer()->get('scheduled_task.repository');
        $nextExecutionTime = new \DateTime();
        $repo->create([
            [
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 5,
                'defaultRunInterval' => 5,
                'status' => ScheduledTaskDefinition::STATUS_SCHEDULED,
                'nextExecutionTime' => $nextExecutionTime,
            ],
            [
                'name' => 'test',
                'scheduledTaskClass' => FooMessage::class,
                'runInterval' => 200,
                'defaultRunInterval' => 200,
                'status' => ScheduledTaskDefinition::STATUS_FAILED,
                'nextExecutionTime' => $nextExecutionTime->modify('-10 seconds'),
            ],
        ], Context::createDefaultContext());

        $url = '/api/_action/scheduled-task/min-run-interval';
        $client = $this->getBrowser();
        $client->request('GET', $url);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        static::assertSame(json_encode(['minRunInterval' => 5]), $client->getResponse()->getContent());
    }
}
