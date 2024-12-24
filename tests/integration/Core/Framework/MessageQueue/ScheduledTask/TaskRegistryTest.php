<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\ScheduledTask;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Cicada\Core\Framework\Test\MessageQueue\fixtures\FooMessage;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\TestTask;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @internal
 */
class TaskRegistryTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $scheduledTaskRepo;

    private TaskRegistry $registry;

    protected function setUp(): void
    {
        $this->scheduledTaskRepo = static::getContainer()->get('scheduled_task.repository');

        $this->registry = new TaskRegistry(
            [
                new TestTask(),
            ],
            $this->scheduledTaskRepo,
            new ParameterBag()
        );
    }

    public function testOnNonRegisteredTask(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $this->registry->registerTasks();

        $tasks = $this->scheduledTaskRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();

        static::assertCount(1, $tasks);
        /** @var ScheduledTaskEntity $task */
        $task = $tasks->first();
        static::assertInstanceOf(ScheduledTaskEntity::class, $task);
        static::assertEquals(TestTask::class, $task->getScheduledTaskClass());
        static::assertEquals(TestTask::getDefaultInterval(), $task->getRunInterval());
        static::assertEquals(TestTask::getTaskName(), $task->getName());
        static::assertEquals(ScheduledTaskDefinition::STATUS_SCHEDULED, $task->getStatus());
    }

    public function testUpdatesRunIntervalOnAlreadyRegisteredTaskWhenRunIntervalMatchesDefault(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $this->scheduledTaskRepo->create([
            [
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 5,
                'defaultRunInterval' => 5,
                'status' => ScheduledTaskDefinition::STATUS_FAILED,
            ],
        ], Context::createDefaultContext());

        $this->registry->registerTasks();

        $tasks = $this->scheduledTaskRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();

        static::assertCount(1, $tasks);
        /** @var ScheduledTaskEntity $task */
        $task = $tasks->first();
        static::assertInstanceOf(ScheduledTaskEntity::class, $task);
        static::assertEquals(TestTask::class, $task->getScheduledTaskClass());
        static::assertEquals(1, $task->getRunInterval());
        static::assertEquals(1, $task->getDefaultRunInterval());
        static::assertEquals('test', $task->getName());
        static::assertEquals(ScheduledTaskDefinition::STATUS_FAILED, $task->getStatus());
    }

    public function testDoesNotUpdateRunIntervalOnAlreadyRegisteredTaskWhenRunIntervalWasChanged(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $this->scheduledTaskRepo->create([
            [
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 5,
                'defaultRunInterval' => 3,
                'status' => ScheduledTaskDefinition::STATUS_FAILED,
            ],
        ], Context::createDefaultContext());

        $this->registry->registerTasks();

        $tasks = $this->scheduledTaskRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();

        static::assertCount(1, $tasks);
        /** @var ScheduledTaskEntity $task */
        $task = $tasks->first();
        static::assertInstanceOf(ScheduledTaskEntity::class, $task);
        static::assertEquals(TestTask::class, $task->getScheduledTaskClass());
        static::assertEquals(5, $task->getRunInterval());
        static::assertEquals(1, $task->getDefaultRunInterval());
        static::assertEquals('test', $task->getName());
        static::assertEquals(ScheduledTaskDefinition::STATUS_FAILED, $task->getStatus());
    }

    public function testWithWrongClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Tried to register "%s" as scheduled task, but class does not extend ScheduledTask',
            FooMessage::class
        ));
        $registry = new TaskRegistry(
            /** @phpstan-ignore-next-line we want to test the exception that phpstan also reports */
            [
                new FooMessage(),
            ],
            $this->scheduledTaskRepo,
            new ParameterBag()
        );

        $registry->registerTasks();
    }

    public function testItDeletesNotAvailableTasks(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM scheduled_task');

        $this->scheduledTaskRepo->create([
            [
                'name' => 'test',
                'scheduledTaskClass' => TestTask::class,
                'runInterval' => 5,
                'defaultRunInterval' => 5,
                'status' => ScheduledTaskDefinition::STATUS_FAILED,
            ],
        ], Context::createDefaultContext());

        $registry = new TaskRegistry([], $this->scheduledTaskRepo, new ParameterBag());
        $registry->registerTasks();

        $tasks = $this->scheduledTaskRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();

        static::assertCount(0, $tasks);
    }
}
