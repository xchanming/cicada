<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: TestTask::class)]
final class DummyScheduledTaskHandler extends ScheduledTaskHandler
{
    private bool $wasCalled = false;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly string $taskId,
        private readonly bool $shouldThrowException = false
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->wasCalled = true;

        /** @var ScheduledTaskEntity $task */
        $task = $this->scheduledTaskRepository
            ->search(new Criteria([$this->taskId]), Context::createCLIContext())
            ->get($this->taskId);

        if ($task->getStatus() !== ScheduledTaskDefinition::STATUS_RUNNING) {
            throw new \Exception('Scheduled Task was not marked as running.');
        }

        if ($this->shouldThrowException) {
            throw new \RuntimeException('This Exception should be thrown');
        }
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}
