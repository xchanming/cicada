<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\ScheduledTask;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\DummyScheduledTaskHandler;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\TestRescheduleOnFailureTask;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\TestTask;

/**
 * @internal
 */
#[CoversClass(ScheduledTaskHandler::class)]
class ScheduledTaskHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    private EntityRepository $scheduledTaskRepo;

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->scheduledTaskRepo = static::getContainer()->get('scheduled_task.repository');
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[DataProvider('allowedStatus')]
    public function testHandle(string $status): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $originalNextExecution = (new \DateTime())->modify('-10 seconds');
        $interval = 300;

        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => $interval,
                'defaultRunInterval' => $interval,
                'status' => $status,
                'nextExecutionTime' => $originalNextExecution,
            ],
        ], Context::createDefaultContext());

        $task = new TestTask();
        $task->setTaskId($taskId);

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId);
        $handler($task);

        static::assertTrue($handler->wasCalled());

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);

        $newOriginalNextExecution = clone $originalNextExecution;
        $newOriginalNextExecution->modify(\sprintf('+%d seconds', $interval));
        $newOriginalNextExecutionString = $newOriginalNextExecution->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $nextExecutionTimeString = $task->getNextExecutionTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        static::assertEquals(ScheduledTaskDefinition::STATUS_SCHEDULED, $task->getStatus());
        static::assertEquals($newOriginalNextExecutionString, $nextExecutionTimeString);
        static::assertNotEquals($originalNextExecution->format(\DATE_ATOM), $task->getNextExecutionTime()->format(\DATE_ATOM));
    }

    /**
     * @return list<array{0: string}>
     */
    public static function allowedStatus(): array
    {
        return [
            [ScheduledTaskDefinition::STATUS_RUNNING],
            [ScheduledTaskDefinition::STATUS_QUEUED],
            [ScheduledTaskDefinition::STATUS_FAILED],
        ];
    }

    public function testHandleWhenNewNextExecutionTimeLessThanNowTime(): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $originalNextExecution = (new \DateTime())->modify('-24 hours');
        $interval = 60;

        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => $interval,
                'defaultRunInterval' => $interval,
                'status' => ScheduledTaskDefinition::STATUS_QUEUED,
                'nextExecutionTime' => $originalNextExecution,
            ],
        ], Context::createDefaultContext());

        $task = new TestTask();
        $task->setTaskId($taskId);

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId);
        $handler($task);
        $nowTime = new \DateTime();

        static::assertTrue($handler->wasCalled());

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);

        static::assertEquals(ScheduledTaskDefinition::STATUS_SCHEDULED, $task->getStatus());
        static::assertGreaterThan(
            $task->getNextExecutionTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            $nowTime->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        );
        static::assertNotEquals($originalNextExecution->format(\DATE_ATOM), $task->getNextExecutionTime()->format(\DATE_ATOM));
    }

    public function testHandleOnException(): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $originalNextExecution = (new \DateTime())->modify('-10 seconds');
        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 300,
                'defaultRunInterval' => 300,
                'status' => ScheduledTaskDefinition::STATUS_QUEUED,
                'nextExecutionTime' => $originalNextExecution,
            ],
        ], Context::createDefaultContext());

        $task = new TestTask();
        $task->setTaskId($taskId);

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId, true);

        $exception = null;

        try {
            $handler($task);
        } catch (\Exception $exception) {
        }

        static::assertInstanceOf(\RuntimeException::class, $exception);
        static::assertEquals('This Exception should be thrown', $exception->getMessage());

        static::assertTrue($handler->wasCalled());

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);
        static::assertEquals(ScheduledTaskDefinition::STATUS_FAILED, $task->getStatus());
    }

    public function testHandleOnExceptionWithRescheduleOnFailure(): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $originalNextExecution = (new \DateTime())->modify('-10 seconds');
        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestRescheduleOnFailureTask::class,
                'runInterval' => 300,
                'defaultRunInterval' => 300,
                'status' => ScheduledTaskDefinition::STATUS_QUEUED,
                'nextExecutionTime' => $originalNextExecution,
            ],
        ], Context::createDefaultContext());

        $task = new TestRescheduleOnFailureTask();
        $task->setTaskId($taskId);

        $this->logger->expects(static::once())->method('error');

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId, true);

        $exception = null;

        try {
            $handler($task);
        } catch (\Exception $exception) {
        }

        static::assertTrue($handler->wasCalled());

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);
        static::assertEquals(ScheduledTaskDefinition::STATUS_SCHEDULED, $task->getStatus());
    }

    public function testHandleIgnoresIfTaskIsNotFound(): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $task = new TestTask();
        $task->setTaskId($taskId);

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId);
        $handler($task);

        static::assertFalse($handler->wasCalled());
    }

    #[DataProvider('notAllowedStatus')]
    public function testHandleIgnoresWhenTaskIsNotAllowedForExecution(string $status): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');

        $taskId = Uuid::randomHex();
        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 300,
                'defaultRunInterval' => 300,
                'status' => $status,
                'nextExecutionTime' => new \DateTime(),
            ],
        ], Context::createDefaultContext());

        $task = new TestTask();
        $task->setTaskId($taskId);

        $handler = new DummyScheduledTaskHandler($this->scheduledTaskRepo, $this->logger, $taskId);
        $handler($task);

        static::assertFalse($handler->wasCalled());

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepo->search(new Criteria([$taskId]), Context::createDefaultContext())->get($taskId);
        static::assertEquals($status, $task->getStatus());
    }

    /**
     * @return list<array{0: string}>
     */
    public static function notAllowedStatus(): array
    {
        return [
            [ScheduledTaskDefinition::STATUS_SCHEDULED],
            [ScheduledTaskDefinition::STATUS_INACTIVE],
        ];
    }
}
