<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\MessageQueue\ScheduledTask\Scheduler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\MinResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Scheduler\TaskScheduler;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[CoversClass(TaskScheduler::class)]
class TaskSchedulerTest extends TestCase
{
    /**
     * @param AggregationResult[] $aggregationResult
     */
    #[DataProvider('providerGetNextExecutionTime')]
    public function testGetNextExecutionTime(array $aggregationResult, ?\DateTime $time): void
    {
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);
        $scheduledTaskRepository->method('aggregate')->willReturn(new AggregationResultCollection($aggregationResult));

        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $this->createMock(MessageBusInterface::class),
            new ParameterBag()
        );

        static::assertEquals(
            $time,
            $scheduler->getNextExecutionTime()
        );
    }

    /**
     * @return iterable<array<AggregationResult[]|\DateTime|null>>
     */
    public static function providerGetNextExecutionTime(): iterable
    {
        yield [
            [],
            null,
        ];

        yield [
            [new TermsResult('nextExecutionTime', [])],
            null,
        ];

        yield [
            [new MinResult('nextExecutionTime', null)],
            null,
        ];

        yield [
            [new MinResult('nextExecutionTime', '2021-01-01T00:00:00+00:00')],
            new \DateTime('2021-01-01T00:00:00+00:00'),
        ];
    }

    /**
     * @param AggregationResult[] $aggregationResult
     */
    #[DataProvider('providerGetMinRunInterval')]
    public function testGetMinRunInterval(array $aggregationResult, ?int $time): void
    {
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);
        $scheduledTaskRepository->method('aggregate')->willReturn(new AggregationResultCollection($aggregationResult));

        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $this->createMock(MessageBusInterface::class),
            new ParameterBag()
        );

        static::assertEquals(
            $time,
            $scheduler->getMinRunInterval()
        );
    }

    /**
     * @return iterable<array<AggregationResult[]|int|null>>
     */
    public static function providerGetMinRunInterval(): iterable
    {
        yield [
            [],
            null,
        ];

        yield [
            [new TermsResult('runInterval', [])],
            null,
        ];

        yield [
            [new MinResult('runInterval', null)],
            null,
        ];

        yield [
            [new MinResult('runInterval', 100)],
            100,
        ];
    }

    public function testScheduleNothingMatches(): void
    {
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);
        $scheduledTaskRepository->expects(static::never())->method('update');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(static::never())->method('dispatch');
        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $bus,
            new ParameterBag()
        );

        $scheduler->queueScheduledTasks();
    }

    public function testScheduleShouldNotRunTask(): void
    {
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);

        $scheduledTask = new ScheduledTaskEntity();

        $nextExecutionTime = new \DateTimeImmutable();
        $nextExecutionTime = $nextExecutionTime->modify(\sprintf('-%d seconds', TestScheduledTask::getDefaultInterval() + 100));

        $scheduledTask->setId('1');
        $scheduledTask->setRunInterval(TestScheduledTask::getDefaultInterval());
        $scheduledTask->setNextExecutionTime($nextExecutionTime);
        $scheduledTask->setScheduledTaskClass(TestScheduledTask::class);
        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getEntities')->willReturn(new ScheduledTaskCollection([$scheduledTask]));
        $scheduledTaskRepository->expects(static::once())->method('search')->willReturn($result);
        $scheduledTaskRepository->expects(static::once())->method('update')->willReturnCallback(function (array $data, Context $context) {
            static::assertCount(1, $data);
            $data = $data[0];
            static::assertArrayHasKey('id', $data);
            static::assertArrayHasKey('nextExecutionTime', $data);
            static::assertArrayHasKey('status', $data);
            static::assertEquals('1', $data['id']);
            static::assertEquals(ScheduledTaskDefinition::STATUS_SKIPPED, $data['status']);

            return new EntityWrittenContainerEvent($context, new NestedEventCollection(), []);
        });

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(static::never())->method('dispatch');
        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $bus,
            new ParameterBag([
                'cicada.test.active' => false,
            ])
        );

        $scheduler->queueScheduledTasks();
    }

    #[DataProvider('providerScheduledTaskQueues')]
    public function testScheduledTaskQueues(bool $shouldSchedule): void
    {
        $scheduledTask = new ScheduledTaskEntity();
        $scheduledTask->setId('1');
        $scheduledTask->setRunInterval(TestScheduledTask::getDefaultInterval());
        $scheduledTask->setNextExecutionTime(new \DateTimeImmutable());
        $scheduledTask->setScheduledTaskClass(TestScheduledTask::class);

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getEntities')->willReturn(new ScheduledTaskCollection([$scheduledTask]));

        $scheduledTaskRepository = $this->createMock(EntityRepository::class);
        $scheduledTaskRepository
            ->method('search')
            ->willReturn($result);

        $scheduledTaskRepository
            ->expects(static::once())
            ->method('update')
            ->willReturnCallback(function (array $data, Context $context) use ($shouldSchedule) {
                static::assertCount(1, $data);
                $data = $data[0];
                static::assertArrayHasKey('status', $data);
                static::assertArrayHasKey('id', $data);
                $status = $data['status'];
                static::assertEquals($shouldSchedule ? ScheduledTaskDefinition::STATUS_QUEUED : ScheduledTaskDefinition::STATUS_SKIPPED, $status);
                static::assertEquals('1', $data['id']);

                return new EntityWrittenContainerEvent($context, new NestedEventCollection(), []);
            });

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($shouldSchedule ? static::once() : static::never())->method('dispatch')->willReturnCallback(function ($message) {
            static::assertInstanceOf(TestScheduledTask::class, $message);

            return new Envelope($message);
        });

        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $bus,
            new ParameterBag(['cicada.test.active' => $shouldSchedule])
        );

        $scheduler->queueScheduledTasks();
    }

    /**
     * @return iterable<array{0: bool}>
     */
    public static function providerScheduledTaskQueues(): iterable
    {
        yield [true];
        yield [false];
    }

    public function testScheduleWithInvalidClass(): void
    {
        $scheduledTask = new ScheduledTaskEntity();
        $scheduledTask->setId('1');
        $scheduledTask->setScheduledTaskClass('foo');

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getEntities')->willReturn(new ScheduledTaskCollection([$scheduledTask]));

        $scheduledTaskRepository = $this->createMock(EntityRepository::class);
        $scheduledTaskRepository
            ->method('search')
            ->willReturn($result);

        $scheduler = new TaskScheduler(
            $scheduledTaskRepository,
            $this->createMock(MessageBusInterface::class),
            new ParameterBag()
        );

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Tried to schedule "foo", but class does not extend ScheduledTask');
        $scheduler->queueScheduledTasks();
    }
}

/**
 * @internal
 */
class TestScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'cicada.test';
    }

    public static function getDefaultInterval(): int
    {
        return 20;
    }

    public static function shouldRun(ParameterBagInterface $bag): bool
    {
        return (bool) $bag->get('cicada.test.active');
    }
}
