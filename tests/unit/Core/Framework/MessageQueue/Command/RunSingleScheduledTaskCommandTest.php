<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\MessageQueue\Command;

use Cicada\Core\Framework\MessageQueue\Command\RunSingleScheduledTaskCommand;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Scheduler\TaskRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(RunSingleScheduledTaskCommand::class)]
class RunSingleScheduledTaskCommandTest extends TestCase
{
    public function testRunSingleTask(): void
    {
        $taskRunner = $this->createMock(TaskRunner::class);
        $taskRunner
            ->expects(static::once())
            ->method('runSingleTask')
            ->with('TestTask.ID');

        $command = new RunSingleScheduledTaskCommand($taskRunner);
        $tester = new CommandTester($command);

        $tester->execute(['taskName' => 'TestTask.ID']);
        $tester->assertCommandIsSuccessful();
    }
}
